<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Data\BookingData;
use App\Domain\Waitlist\Actions\OfferFreedSlot;
use App\Domain\Waitlist\Jobs\ExpireOfferJob;
use App\Enums\BookingStatus;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaitlistAutoFillTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_booking_automatically_offers_freed_slot_to_waitlist_candidate(): void
    {
        Queue::fake([ExpireOfferJob::class]);

        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $client1 = User::factory()->client()->create();
        $client2 = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addDays(2)->setTime(14, 0);

        // Booking for client1
        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client1->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
        ));

        // Client2 is waiting on waitlist for same date and service
        $waitlistEntry = WaitlistEntry::create([
            'business_id' => $business->id,
            'client_user_id' => $client2->id,
            'service_id' => $service->id,
            'preferred_date' => $startAt->toDateString(),
            'status' => 'waiting',
        ]);

        // Cancel booking for client1 -> listener triggers OfferFreedSlot
        (new CancelBooking)->execute($booking, $client1->id, 'Schedule conflict');

        $waitlistEntry->refresh();

        $this->assertSame('offered', $waitlistEntry->status);
        $this->assertNotNull($waitlistEntry->claim_token);
        $this->assertNotNull($waitlistEntry->offered_at);
        $this->assertNotNull($waitlistEntry->offer_expires_at);
        $this->assertSame($booking->id, $waitlistEntry->freed_booking_id);
        $this->assertTrue($waitlistEntry->isClaimable());

        Queue::assertPushed(ExpireOfferJob::class, function ($job) use ($waitlistEntry) {
            return $job->entry->id === $waitlistEntry->id;
        });
    }

    public function test_candidate_can_claim_freed_slot_and_receive_confirmed_booking(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $client1 = User::factory()->client()->create();
        $client2 = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addDays(3)->setTime(11, 0);

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client1->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
        ));

        $waitlistEntry = WaitlistEntry::create([
            'business_id' => $business->id,
            'client_user_id' => $client2->id,
            'service_id' => $service->id,
            'preferred_date' => $startAt->toDateString(),
            'status' => 'waiting',
        ]);

        (new CancelBooking)->execute($booking);

        $waitlistEntry->refresh();
        $token = $waitlistEntry->claim_token;

        // Inspect offer endpoint
        $response = $this->getJson("/waitlist/claim/{$token}");
        $response->assertStatus(200)
            ->assertJsonPath('is_claimable', true)
            ->assertJsonPath('status', 'offered');

        // Claim slot via API endpoint
        $claimResponse = $this->postJson("/waitlist/claim/{$token}");
        $claimResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $waitlistEntry->refresh();
        $this->assertSame('claimed', $waitlistEntry->status);

        // Verify client2 now has a confirmed booking for that slot
        $newBooking = $client2->bookings()->latest()->first();
        $this->assertNotNull($newBooking);
        $this->assertSame(BookingStatus::Confirmed, $newBooking->status);
        $this->assertTrue($newBooking->start_at->equalTo($startAt));
        $this->assertSame($service->id, $newBooking->service_id);
    }

    public function test_expired_offer_cascades_to_second_waitlist_candidate(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $client1 = User::factory()->client()->create();
        $client2 = User::factory()->client()->create();
        $client3 = User::factory()->client()->create();
        $employee = User::factory()->employee()->create();

        $startAt = Carbon::now()->addDays(2)->setTime(16, 0);

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client1->id,
            employeeUserId: $employee->id,
            startAt: $startAt,
        ));

        // Candidate 2 registered earlier
        $candidate2 = WaitlistEntry::create([
            'business_id' => $business->id,
            'client_user_id' => $client2->id,
            'service_id' => $service->id,
            'preferred_date' => $startAt->toDateString(),
            'status' => 'waiting',
            'created_at' => now()->subHours(2),
        ]);

        // Candidate 3 registered later
        $candidate3 = WaitlistEntry::create([
            'business_id' => $business->id,
            'client_user_id' => $client3->id,
            'service_id' => $service->id,
            'preferred_date' => $startAt->toDateString(),
            'status' => 'waiting',
            'created_at' => now()->subHour(),
        ]);

        // Cancel original booking -> offered to candidate 2 first
        (new CancelBooking)->execute($booking);

        $candidate2->refresh();
        $this->assertSame('offered', $candidate2->status);
        $this->assertSame('waiting', $candidate3->fresh()->status);

        // Simulate 15-min timeout and execution of ExpireOfferJob
        $candidate2->update(['offer_expires_at' => now()->subMinute()]);
        $job = new ExpireOfferJob($candidate2);
        app()->call([$job, 'handle']);

        $candidate2->refresh();
        $candidate3->refresh();

        $this->assertSame('expired', $candidate2->status);
        $this->assertSame('offered', $candidate3->status);
        $this->assertNotNull($candidate3->claim_token);
        $this->assertTrue($candidate3->isClaimable());
    }

    public function test_authenticated_client_can_join_waitlist(): void
    {
        $business = Business::factory()->create();
        $service = Service::factory()->create(['business_id' => $business->id]);
        $client = User::factory()->client()->create();

        $payload = [
            'business_id' => $business->id,
            'service_id' => $service->id,
            'preferred_date' => now()->addDays(5)->toDateString(),
            'preferred_time_from' => '10:00',
            'preferred_time_to' => '14:00',
        ];

        $response = $this->actingAs($client)->postJson('/api/waitlist/join', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('entry.status', 'waiting');

        $this->assertEquals(1, WaitlistEntry::where('client_user_id', $client->id)->count());
    }

    public function test_guests_cannot_join_the_waitlist(): void
    {
        $business = Business::factory()->create();
        $service = Service::factory()->create(['business_id' => $business->id]);

        $this->postJson('/api/waitlist/join', [
            'business_id' => $business->id,
            'service_id' => $service->id,
            'preferred_date' => now()->addDays(5)->toDateString(),
        ])->assertUnauthorized();

        $this->assertEquals(0, WaitlistEntry::count());
    }
}
