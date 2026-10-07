<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\MarkNoShow;
use App\Domain\Booking\Actions\RescheduleBooking;
use App\Domain\Booking\Data\BookingData;
use App\Domain\Booking\Events\BookingCancelledEvent;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Enums\BookingStatus;
use App\Enums\HistoryAction;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_booking_action_creates_appointment_and_schedules_reminders(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duration_minutes' => 45,
            'price' => 40.00,
            'booking_fee' => 5.00,
        ]);
        $client = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addDays(5)->setTime(10, 0);

        $action = new CreateBooking;
        $booking = $action->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
            clientNote: 'First time visit',
            depositAmount: 10.00,
            depositStatus: 'paid',
            riskScore: 0.25,
            riskTier: 'low',
        ));

        $this->assertNotNull($booking->id);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('45.00', $booking->total_amount);
        $this->assertSame('10.00', $booking->deposit_amount);
        $this->assertSame('paid', $booking->deposit_status);
        $this->assertNotNull($booking->deposit_paid_at);

        // Check reminders scheduled (48h, 24h, 2h)
        $this->assertCount(3, $booking->reminders);

        // Check history
        $this->assertCount(1, $booking->history);
        $this->assertSame(HistoryAction::Booked, $booking->history->first()->action);
    }

    public function test_create_booking_blocks_overlapping_slot_with_exception(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duration_minutes' => 60,
        ]);
        $client1 = User::factory()->client()->create();
        $client2 = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addDays(3)->setTime(15, 0);

        $action = new CreateBooking;
        $action->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client1->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
        ));

        // Overlapping attempt (15:30 to 16:30)
        $this->expectException(SlotAlreadyBookedException::class);

        $action->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client2->id,
            employeeUserId: $employee->id,
            startAt: $startAt->copy()->addMinutes(30),
        ));
    }

    public function test_cancel_booking_calculates_fees_and_fires_waitlist_event(): void
    {
        Event::fake([BookingCancelledEvent::class]);

        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'cancellation_fee' => 20.00,
            'free_cancellation_hours' => 24,
        ]);
        $client = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addHours(6); // Inside 24h window -> fee applies

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
        ));

        $this->assertSame(BookingStatus::Confirmed, $booking->status);

        $cancelled = (new CancelBooking)->execute($booking, $client->id, 'Family emergency');

        $this->assertSame(BookingStatus::Cancelled, $cancelled->status);
        $this->assertSame('20.00', $cancelled->cancellation_fee);
        $this->assertNotNull($cancelled->cancelled_at);

        // Assert pending reminders are marked cancelled
        $this->assertEquals(0, $cancelled->reminders()->where('delivery_status', 'scheduled')->count());

        Event::assertDispatched(BookingCancelledEvent::class, function ($event) use ($booking) {
            return $event->booking->id === $booking->id && $event->reason === 'Family emergency';
        });
    }

    public function test_reschedule_booking_updates_window_and_reminders(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $client = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: $employee->id,
            startAt: Carbon::now()->addDays(2)->setTime(10, 0),
        ));

        $newTime = Carbon::now()->addDays(4)->setTime(14, 0);

        $rescheduled = (new RescheduleBooking)->execute($booking, $newTime);

        $this->assertTrue($rescheduled->start_at->equalTo($newTime));
        $this->assertTrue($rescheduled->end_at->equalTo($newTime->copy()->addMinutes(30)));

        $historyActions = $rescheduled->history()->pluck('action')->toArray();
        $this->assertContains(HistoryAction::Rescheduled, $historyActions);
    }

    public function test_mark_no_show_forfeits_paid_deposit(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $client = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: $employee->id,
            startAt: Carbon::now()->addDays(1)->setTime(10, 0),
            depositAmount: 15.00,
            depositStatus: 'paid',
        ));

        $this->assertSame('paid', $booking->deposit_status);

        $noShowBooking = (new MarkNoShow)->execute($booking, 'Client did not show up.');

        $this->assertSame(BookingStatus::NoShow, $noShowBooking->status);
        $this->assertSame('forfeited', $noShowBooking->deposit_status);
        $this->assertNotNull($noShowBooking->completed_at);
    }
}
