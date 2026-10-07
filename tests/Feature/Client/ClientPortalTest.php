<?php

namespace Tests\Feature\Client;

use App\Domain\Risk\RiskScoreClient;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    private Location $location;

    private Service $service;

    private User $employee;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 09:00:00'));

        $this->owner = User::factory()->owner()->create();
        $this->business = Business::factory()->create(['owner_user_id' => $this->owner->id]);
        $this->location = Location::factory()->create(['business_id' => $this->business->id]);
        $this->service = Service::factory()->create([
            'business_id' => $this->business->id,
            'booking_fee' => 0,
            'price' => 100,
        ]);
        $this->employee = User::factory()->employee()->create();
        $this->client = User::factory()->client()->create(['phone' => '+351900000001']);
    }

    private function makeBooking(User $client, Carbon $start, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'client_user_id' => $client->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(30),
        ], $overrides));
    }

    public function test_booking_endpoints_require_authentication(): void
    {
        $this->postJson(route('api.booking.store'), [
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'start_at' => Carbon::tomorrow()->setHour(14),
        ])->assertUnauthorized();

        $this->postJson(route('api.booking.risk_preview'), [
            'service_id' => $this->service->id,
            'start_at' => Carbon::tomorrow()->setHour(14),
        ])->assertUnauthorized();
    }

    public function test_booking_is_created_for_the_signed_in_client(): void
    {
        $mockRisk = $this->createMock(RiskScoreClient::class);
        $mockRisk->method('calculateRiskScore')->willReturn([
            'risk_score' => 0.1,
            'risk_tier' => 'low',
            'requires_deposit' => false,
            'suggested_deposit_amount' => 0.0,
            'factors' => [],
        ]);
        $this->app->instance(RiskScoreClient::class, $mockRisk);

        $response = $this->actingAs($this->client)->postJson(route('api.booking.store'), [
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $this->employee->id,
            'start_at' => Carbon::tomorrow()->setHour(14)->toIso8601String(),
            'client_phone' => '+351911222333',
        ]);

        $response->assertCreated();

        $ref = $response->json('reference_code');
        $this->assertDatabaseHas('bookings', [
            'reference_code' => $ref,
            'client_user_id' => $this->client->id,
        ]);
        $this->assertEquals('+351911222333', $this->client->fresh()->phone);
    }

    public function test_client_dashboard_shows_history_spend_and_fees(): void
    {
        $this->makeBooking($this->client, Carbon::parse('2026-10-10 10:00:00')); // upcoming

        $this->makeBooking($this->client, Carbon::parse('2026-09-20 11:00:00'), [
            'status' => BookingStatus::Completed,
            'total_amount' => 100,
        ]);

        $this->makeBooking($this->client, Carbon::parse('2026-09-01 11:00:00'), [
            'status' => BookingStatus::Cancelled,
            'total_amount' => 40,
            'cancellation_fee' => 25,
        ]);

        // Another client's history must never leak in.
        $other = User::factory()->client()->create();
        $this->makeBooking($other, Carbon::parse('2026-09-15 11:00:00'), [
            'status' => BookingStatus::Completed,
            'total_amount' => 999,
            'cancellation_fee' => 50,
        ]);

        $response = $this->actingAs($this->client)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('client/dashboard')
            ->where('profile.name', $this->client->name)
            ->where('summary.upcoming', 1)
            ->where('summary.visits', 1)
            ->where('summary.total_spent', fn ($v) => $v == 100)
            ->where('summary.cancellation_fees', fn ($v) => $v == 25)
            ->has('upcoming', 1)
            ->has('past', 2)
            ->where('past.0.reviewable', true)
        );
    }

    public function test_client_dashboard_handles_a_fresh_account(): void
    {
        $fresh = User::factory()->client()->create();

        $response = $this->actingAs($fresh)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('client/dashboard')
            ->where('summary.upcoming', 0)
            ->where('summary.visits', 0)
            ->where('summary.total_spent', fn ($v) => $v == 0)
            ->where('summary.cancellation_fees', fn ($v) => $v == 0)
            ->where('upcoming', [])
            ->where('past', [])
        );
    }

    public function test_client_can_review_their_completed_booking(): void
    {
        $booking = $this->makeBooking($this->client, Carbon::parse('2026-09-20 11:00:00'), [
            'status' => BookingStatus::Completed,
        ]);

        $response = $this->actingAs($this->client)->postJson(
            route('api.bookings.review', ['booking' => $booking->id]),
            ['rating' => 5, 'body' => 'Best cut in town.']
        );

        $response->assertCreated();
        $response->assertJsonPath('review.rating', 5);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'client_user_id' => $this->client->id,
            'rating' => 5,
        ]);
    }

    public function test_review_endpoint_requires_authentication(): void
    {
        $booking = $this->makeBooking($this->client, Carbon::parse('2026-09-20 11:00:00'), [
            'status' => BookingStatus::Completed,
        ]);

        $this->postJson(route('api.bookings.review', ['booking' => $booking->id]), [
            'rating' => 5,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_rules_are_enforced(): void
    {
        $completed = $this->makeBooking($this->client, Carbon::parse('2026-09-20 11:00:00'), [
            'status' => BookingStatus::Completed,
        ]);
        $pending = $this->makeBooking($this->client, Carbon::parse('2026-09-21 11:00:00'));

        $otherClient = User::factory()->client()->create();
        $foreign = $this->makeBooking($otherClient, Carbon::parse('2026-09-22 11:00:00'), [
            'status' => BookingStatus::Completed,
        ]);

        // Not completed yet.
        $this->actingAs($this->client)
            ->postJson(route('api.bookings.review', ['booking' => $pending->id]), ['rating' => 5])
            ->assertForbidden();

        // Someone else's appointment.
        $this->actingAs($this->client)
            ->postJson(route('api.bookings.review', ['booking' => $foreign->id]), ['rating' => 5])
            ->assertForbidden();

        // Rating out of range.
        $this->actingAs($this->client)
            ->postJson(route('api.bookings.review', ['booking' => $completed->id]), ['rating' => 6])
            ->assertStatus(422);

        // One review per appointment.
        $this->actingAs($this->client)
            ->postJson(route('api.bookings.review', ['booking' => $completed->id]), ['rating' => 4])
            ->assertCreated();

        $this->actingAs($this->client)
            ->postJson(route('api.bookings.review', ['booking' => $completed->id]), ['rating' => 2])
            ->assertForbidden();

        $this->assertEquals(1, Review::count());
    }
}
