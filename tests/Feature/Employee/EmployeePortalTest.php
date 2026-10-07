<?php

namespace Tests\Feature\Employee;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    private Location $location;

    private Service $service;

    private User $employee;

    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 09:00:00'));

        $this->owner = User::factory()->owner()->create();
        $this->business = Business::factory()->create(['owner_user_id' => $this->owner->id]);
        $this->location = Location::factory()->create(['business_id' => $this->business->id]);
        $this->service = Service::factory()->create(['business_id' => $this->business->id]);

        $this->employee = User::factory()->employee()->create();
        $this->location->employees()->attach($this->employee->id, ['is_active' => true]);
        $this->service->employees()->attach($this->employee->id);

        $this->stranger = User::factory()->client()->create();
    }

    private function makeBooking(User $employee, Carbon $start, array $overrides = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
            'service_id' => $this->service->id,
            'employee_user_id' => $employee->id,
            'client_user_id' => User::factory()->client()->create()->id,
            'status' => BookingStatus::Confirmed,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(30),
        ], $overrides));
    }

    public function test_employee_sees_their_own_schedule_for_today(): void
    {
        $mine = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));
        $colleague = User::factory()->employee()->create();
        $this->makeBooking($colleague, Carbon::parse('2026-10-07 11:00:00'));

        $response = $this->actingAs($this->employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('employee/dashboard')
            ->where('profile.name', $this->employee->name)
            ->has('profile.locations', 1)
            ->has('profile.services', 1)
            ->where('selected_date', '2026-10-07')
            ->where('today', '2026-10-07')
            ->where('summary.total', 1)
            ->where('summary.completed', 0)
            ->where('summary.remaining', 1)
            ->where('summary.week_total', 1)
            ->has('appointments', 1)
            ->where('appointments.0.id', $mine->id)
        );
    }

    public function test_employee_day_navigation_and_invalid_date_fallback(): void
    {
        $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));
        $tomorrow = $this->makeBooking($this->employee, Carbon::parse('2026-10-08 14:00:00'));

        $response = $this->actingAs($this->employee)->get(route('dashboard', ['date' => '2026-10-08']));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('employee/dashboard')
            ->where('selected_date', '2026-10-08')
            ->where('summary.total', 1)
            ->where('appointments.0.id', $tomorrow->id)
        );

        $response = $this->actingAs($this->employee)->get(route('dashboard', ['date' => 'not-a-date']));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('employee/dashboard')
            ->where('selected_date', '2026-10-07')
            ->where('summary.total', 1)
        );
    }

    public function test_employee_dashboard_handles_an_empty_day(): void
    {
        $response = $this->actingAs($this->employee)->get(route('dashboard', ['date' => '2026-11-01']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('employee/dashboard')
            ->where('summary.total', 0)
            ->where('summary.week_total', 0)
            ->where('appointments', [])
        );
    }

    public function test_owner_still_gets_the_owner_kpi_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('kpis.no_show_rate')
            ->has('appointments')
        );
    }

    public function test_check_in_requires_authentication(): void
    {
        $booking = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));

        $this->postJson(route('api.bookings.check_in', ['booking' => $booking->id]))
            ->assertUnauthorized();
    }

    public function test_check_in_is_forbidden_for_unrelated_users(): void
    {
        $booking = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));

        $this->actingAs($this->stranger)
            ->postJson(route('api.bookings.check_in', ['booking' => $booking->id]))
            ->assertForbidden();

        $this->assertEquals(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_employee_can_check_in_their_own_booking(): void
    {
        $booking = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));

        $this->actingAs($this->employee)
            ->postJson(route('api.bookings.check_in', ['booking' => $booking->id]))
            ->assertOk();

        $this->assertEquals(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_offline_sync_requires_authentication(): void
    {
        $this->postJson(route('api.offline.sync'), [
            'actions' => [
                [
                    'id' => 'a1',
                    'type' => 'check_in',
                    'booking_id' => 1,
                    'timestamp' => now()->timestamp,
                ],
            ],
        ])->assertUnauthorized();
    }

    public function test_offline_sync_is_forbidden_for_unrelated_users(): void
    {
        $booking = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));

        $this->actingAs($this->stranger)->postJson(route('api.offline.sync'), [
            'actions' => [
                [
                    'id' => 'a1',
                    'type' => 'check_in',
                    'booking_id' => $booking->id,
                    'timestamp' => now()->timestamp,
                ],
            ],
        ])->assertForbidden();

        $this->assertEquals(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_unrelated_users_cannot_use_mark_no_show_or_send_reminder(): void
    {
        $booking = $this->makeBooking($this->employee, Carbon::parse('2026-10-07 10:00:00'));

        $this->actingAs($this->stranger)
            ->postJson(route('api.bookings.mark_no_show', ['booking' => $booking->id]))
            ->assertForbidden();

        $this->actingAs($this->stranger)
            ->postJson(route('api.bookings.send_reminder', ['booking' => $booking->id]))
            ->assertForbidden();

        $this->assertEquals(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseCount('reminders', 0);
    }
}
