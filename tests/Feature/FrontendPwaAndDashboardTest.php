<?php

namespace Tests\Feature;

use App\Domain\Risk\RiskScoreClient;
use App\Enums\BookingStatus;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FrontendPwaAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Location $location;

    protected Service $service;

    protected User $owner;

    protected User $employee;

    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $businessType = BusinessType::firstOrCreate(
            ['slug' => 'barbershop'],
            ['name' => 'Barbershop', 'description' => 'Haircuts and styling']
        );

        $this->owner = User::factory()->create([
            'role' => UserRole::Owner,
            'email' => 'owner@aurora.test',
        ]);

        $this->employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Marco Silva',
        ]);

        $this->client = User::factory()->create([
            'role' => UserRole::Client,
            'name' => 'Carlos Gomes',
            'phone' => '+351912345678',
        ]);

        $this->business = Business::create([
            'business_type_id' => $businessType->id,
            'owner_user_id' => $this->owner->id,
            'name' => 'Aurora Hair Studio',
            'slug' => 'aurora-hair-studio',
            'email' => 'contact@aurora.test',
            'address_line1' => 'Rua Augusta 45',
            'city' => 'Lisbon',
            'country' => 'Portugal',
            'timezone' => 'Europe/Lisbon',
            'status' => BusinessStatus::Active,
            'cancellation_notice' => 'Free cancellation up to 24 hours prior.',
        ]);

        $this->location = Location::create([
            'business_id' => $this->business->id,
            'name' => 'Downtown Lisboa',
            'address_line1' => 'Rua Augusta 45, Lisboa',
            'city' => 'Lisbon',
            'country' => 'Portugal',
            'timezone' => 'Europe/Lisbon',
            'max_capacity' => 5,
        ]);
        $this->location->employees()->attach($this->employee->id, ['is_active' => true]);

        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Signature Haircut',
            'description' => 'Precision cut with warm towel finish',
            'price' => 45.00,
            'duration_minutes' => 45,
            'booking_fee' => 0.00,
            'is_active' => true,
            'is_recommended' => true,
        ]);
        $this->service->locations()->attach($this->location->id, ['is_active' => true]);
        $this->service->employees()->attach($this->employee->id);
    }

    public function test_public_booking_page_renders_with_business_details(): void
    {
        $response = $this->get(route('booking.index', ['slug' => $this->business->slug]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('booking/index')
            ->has('business.name')
            ->where('business.slug', 'aurora-hair-studio')
            ->has('business.services', 1)
        );
    }

    public function test_available_slots_endpoint_calculates_slots_with_buffer_time(): void
    {
        $targetDate = Carbon::tomorrow()->toDateString();

        // Create an existing booking from 10:00 to 10:45 on that day
        Booking::create([
            'reference_code' => 'BK-TEST01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => Carbon::parse("{$targetDate} 10:00:00"),
            'end_at' => Carbon::parse("{$targetDate} 10:45:00"),
            'duration_minutes' => 45,
            'buffer_minutes' => 10, // Occupies until 10:55
            'party_size' => 1,
            'service_price' => 45.00,
            'total_amount' => 45.00,
            'currency' => 'USD',
        ]);

        $response = $this->getJson(route('api.booking.available_slots', [
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'date' => $targetDate,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'date',
            'service_duration',
            'buffer_minutes',
            'available_slots',
            'is_fully_booked',
        ]);

        $slots = collect($response->json('available_slots'));

        // 10:00 should NOT be available (overlaps existing)
        $this->assertFalse($slots->contains('time', '10:00'));

        // 10:30 should NOT be available (conflicts with 10:45 end + 10m buffer = 10:55)
        $this->assertFalse($slots->contains('time', '10:30'));

        // 11:00 SHOULD be available (starts after 10:55 buffer)
        $this->assertTrue($slots->contains('time', '11:00'));
    }

    public function test_booking_risk_preview_endpoint_evaluates_risk_and_deposit(): void
    {
        $startAt = Carbon::tomorrow()->setHour(14)->toIso8601String();

        $response = $this->actingAs($this->client)->postJson(route('api.booking.risk_preview'), [
            'service_id' => $this->service->id,
            'start_at' => $startAt,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'risk_score',
            'risk_tier',
            'requires_deposit',
            'deposit_amount',
            'reason',
        ]);
    }

    public function test_booking_creation_enforces_deposit_when_required(): void
    {
        // Mock RiskScoreClient to simulate a high-risk score
        $mockRisk = $this->createMock(RiskScoreClient::class);
        $mockRisk->method('calculateRiskScore')->willReturn([
            'risk_score' => 0.78,
            'risk_tier' => 'high',
            'requires_deposit' => true,
            'suggested_deposit_amount' => 15.00,
            'factors' => ['First-time client', 'High-demand slot'],
        ]);
        $this->app->instance(RiskScoreClient::class, $mockRisk);

        $startAt = Carbon::tomorrow()->setHour(15)->toIso8601String();

        // Attempt booking without confirming deposit
        $response = $this->actingAs($this->client)->postJson(route('api.booking.store'), [
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'start_at' => $startAt,
            'client_phone' => '+351912999888',
            'deposit_confirmed' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'deposit_required',
            'deposit_amount' => 15.00,
        ]);
    }

    public function test_booking_creation_succeeds_when_deposit_confirmed(): void
    {
        // Mock RiskScoreClient with high risk
        $mockRisk = $this->createMock(RiskScoreClient::class);
        $mockRisk->method('calculateRiskScore')->willReturn([
            'risk_score' => 0.78,
            'risk_tier' => 'high',
            'requires_deposit' => true,
            'suggested_deposit_amount' => 15.00,
            'factors' => ['High risk'],
        ]);
        $this->app->instance(RiskScoreClient::class, $mockRisk);

        $startAt = Carbon::tomorrow()->setHour(16)->toIso8601String();

        // Submit with deposit_confirmed = true
        $response = $this->actingAs($this->client)->postJson(route('api.booking.store'), [
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'start_at' => $startAt,
            'client_phone' => '+351912999888',
            'deposit_confirmed' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['status', 'reference_code', 'booking_id', 'redirect_url']);

        $ref = $response->json('reference_code');
        $this->assertDatabaseHas('bookings', [
            'reference_code' => $ref,
            'deposit_status' => 'paid',
            'deposit_amount' => 15.00,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_booking_confirmation_page_renders_with_calendar_data(): void
    {
        $booking = Booking::create([
            'reference_code' => 'BK-CONFIRM01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => Carbon::tomorrow()->setHour(11),
            'end_at' => Carbon::tomorrow()->setHour(11)->addMinutes(45),
            'duration_minutes' => 45,
            'buffer_minutes' => 10,
            'party_size' => 1,
            'service_price' => 45.00,
            'total_amount' => 45.00,
            'currency' => 'USD',
            'deposit_status' => 'paid',
            'deposit_amount' => 15.00,
        ]);

        $response = $this->get(route('booking.confirmation', ['reference_code' => $booking->reference_code]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('booking/confirmation')
            ->where('booking.reference_code', 'BK-CONFIRM01')
            ->where('booking.deposit_amount', fn ($val) => (float) $val === 15.0)
        );
    }

    public function test_waitlist_claim_page_renders_with_countdown(): void
    {
        $freedBooking = Booking::create([
            'reference_code' => 'BK-FREED01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Cancelled,
            'start_at' => Carbon::tomorrow()->setHour(14),
            'end_at' => Carbon::tomorrow()->setHour(14)->addMinutes(45),
            'duration_minutes' => 45,
            'total_amount' => 45.00,
            'currency' => 'USD',
        ]);

        $entry = WaitlistEntry::create([
            'business_id' => $this->business->id,
            'client_user_id' => $this->client->id,
            'service_id' => $this->service->id,
            'preferred_date' => Carbon::tomorrow(),
            'status' => 'offered',
            'claim_token' => 'test-claim-token-12345',
            'freed_booking_id' => $freedBooking->id,
            'offer_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->get(route('waitlist.claim.show', ['token' => $entry->claim_token]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('waitlist/claim')
            ->where('offer.token', 'test-claim-token-12345')
            ->where('offer.is_claimable', true)
        );
    }

    public function test_owner_dashboard_returns_kpis_and_ledger(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('kpis.no_show_rate')
            ->has('kpis.recovered_revenue')
            ->has('funnel.sent')
            ->has('appointments')
            ->has('waitlist')
        );
    }

    public function test_dashboard_staff_quick_actions(): void
    {
        $booking = Booking::create([
            'reference_code' => 'BK-CHECKIN01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => Carbon::now()->subMinutes(10),
            'end_at' => Carbon::now()->addMinutes(35),
            'duration_minutes' => 45,
            'total_amount' => 45.00,
            'currency' => 'USD',
            'deposit_status' => 'paid',
            'deposit_amount' => 15.00,
        ]);

        // 1. Staff Check-in
        $response = $this->actingAs($this->owner)->postJson(route('api.bookings.check_in', ['booking' => $booking->id]));
        $response->assertOk();
        $this->assertEquals(BookingStatus::Completed, $booking->fresh()->status);

        // 2. Staff Mark No-Show on another booking
        $noShowBooking = Booking::create([
            'reference_code' => 'BK-NOSHOW01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => Carbon::now()->subHour(),
            'end_at' => Carbon::now()->subMinutes(15),
            'duration_minutes' => 45,
            'total_amount' => 45.00,
            'currency' => 'USD',
            'deposit_status' => 'paid',
            'deposit_amount' => 15.00,
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('api.bookings.mark_no_show', ['booking' => $noShowBooking->id]));
        $response->assertOk();
        $this->assertEquals(BookingStatus::NoShow, $noShowBooking->fresh()->status);
        $this->assertEquals('forfeited', $noShowBooking->fresh()->deposit_status);
    }

    public function test_offline_sync_endpoint_replays_queued_actions(): void
    {
        $booking = Booking::create([
            'reference_code' => 'BK-OFFLINE01',
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $this->client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => Carbon::now()->subMinutes(20),
            'end_at' => Carbon::now()->addMinutes(25),
            'duration_minutes' => 45,
            'total_amount' => 45.00,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($this->employee)->postJson(route('api.offline.sync'), [
            'actions' => [
                [
                    'id' => 'action_123',
                    'type' => 'check_in',
                    'booking_id' => $booking->id,
                    'timestamp' => now()->timestamp,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'synced_count' => 1,
        ]);

        $this->assertEquals(BookingStatus::Completed, $booking->fresh()->status);
    }
}
