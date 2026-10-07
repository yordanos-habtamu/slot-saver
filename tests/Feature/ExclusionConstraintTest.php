<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExclusionConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_model_supports_deposit_and_risk_fields(): void
    {
        $booking = Booking::factory()->create([
            'deposit_amount' => 25.00,
            'deposit_status' => 'paid',
            'deposit_paid_at' => now(),
            'risk_score' => 0.7250,
            'risk_tier' => 'high',
            'buffer_minutes' => 15,
        ]);

        $fresh = $booking->fresh();
        $this->assertSame('25.00', $fresh->deposit_amount);
        $this->assertSame('paid', $fresh->deposit_status);
        $this->assertNotNull($fresh->deposit_paid_at);
        $this->assertSame('0.7250', $fresh->risk_score);
        $this->assertSame('high', $fresh->risk_tier);
        $this->assertSame(15, $fresh->buffer_minutes);
    }

    public function test_reminders_can_be_associated_with_bookings_and_queried(): void
    {
        $booking = Booking::factory()->create();

        $reminder = Reminder::create([
            'booking_id' => $booking->id,
            'channel' => 'whatsapp',
            'type' => '24h',
            'scheduled_for' => now()->subMinute(),
            'delivery_status' => 'scheduled',
        ]);

        $this->assertCount(1, $booking->reminders);
        $this->assertSame('whatsapp', $booking->reminders->first()->channel);

        $dueReminders = Reminder::due()->get();
        $this->assertTrue($dueReminders->contains($reminder));
    }

    public function test_waitlist_entry_lifecycle_and_claimability(): void
    {
        $business = Business::factory()->create();
        $client = User::factory()->client()->create();
        $service = Service::factory()->create(['business_id' => $business->id]);

        $entry = WaitlistEntry::create([
            'business_id' => $business->id,
            'client_user_id' => $client->id,
            'service_id' => $service->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'status' => 'waiting',
        ]);

        $this->assertSame('waiting', $entry->status);
        $this->assertFalse($entry->isClaimable());

        $token = WaitlistEntry::generateClaimToken();
        $entry->update([
            'status' => 'offered',
            'claim_token' => $token,
            'offered_at' => now(),
            'offer_expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue($entry->fresh()->isClaimable());

        // Fast-forward past expiry
        $entry->update(['offer_expires_at' => now()->subMinute()]);
        $this->assertFalse($entry->fresh()->isClaimable());
        $this->assertTrue(WaitlistEntry::expiredOffers()->get()->contains($entry));
    }

    public function test_webhook_event_idempotency_ledger(): void
    {
        $idempotencyKey = 'whatsapp:wamid.HBgM123456789';

        $this->assertFalse(WebhookEvent::isDuplicate($idempotencyKey));

        WebhookEvent::create([
            'provider' => 'whatsapp',
            'idempotency_key' => $idempotencyKey,
            'event_type' => 'messages.button_reply',
            'payload' => ['button' => 'confirm'],
            'processed_at' => now(),
            'response_status' => 'success',
        ]);

        $this->assertTrue(WebhookEvent::isDuplicate($idempotencyKey));

        // Duplicate insert should throw QueryException
        $this->expectException(QueryException::class);
        WebhookEvent::create([
            'provider' => 'whatsapp',
            'idempotency_key' => $idempotencyKey,
            'event_type' => 'messages.button_reply',
            'payload' => ['button' => 'confirm'],
        ]);
    }

    public function test_postgresql_exclusion_constraint_blocks_overlapping_staff_bookings(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-specific exclusion constraint test.');
        }

        $business = Business::factory()->create();
        $employee = User::factory()->employee()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $client1 = User::factory()->client()->create();
        $client2 = User::factory()->client()->create();

        $start = Carbon::now()->addDays(2)->setTime(14, 0);
        $end = $start->copy()->addMinutes(45);

        Booking::create([
            'reference_code' => 'SS-TEST01',
            'client_user_id' => $client1->id,
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'employee_user_id' => $employee->id,
            'status' => BookingStatus::Confirmed->value,
            'start_at' => $start,
            'end_at' => $end,
            'duration_minutes' => 45,
            'total_amount' => 50.00,
        ]);

        // Attempt overlapping booking with same employee
        $overlapStart = $start->copy()->addMinutes(15);
        $overlapEnd = $overlapStart->copy()->addMinutes(45);

        $this->expectException(QueryException::class);

        Booking::create([
            'reference_code' => 'SS-TEST02',
            'client_user_id' => $client2->id,
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'employee_user_id' => $employee->id,
            'status' => BookingStatus::Pending->value,
            'start_at' => $overlapStart,
            'end_at' => $overlapEnd,
            'duration_minutes' => 45,
            'total_amount' => 50.00,
        ]);
    }
}
