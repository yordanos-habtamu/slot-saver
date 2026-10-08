<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_gets_platform_overview_instead_of_a_single_business(): void
    {
        $first = Business::factory()->create(['name' => 'Aurora Hair Salon']);
        $second = Business::factory()->create(['name' => 'Coast Nail Bar']);

        $firstService = Service::factory()->create(['business_id' => $first->id, 'price' => 60]);
        Booking::factory()->create(['business_id' => $first->id, 'service_id' => $firstService->id]);
        Booking::factory()->noShow()->create(['business_id' => $first->id, 'service_id' => $firstService->id]);

        $secondService = Service::factory()->create(['business_id' => $second->id, 'price' => 40]);
        Booking::factory()->create(['business_id' => $second->id, 'service_id' => $secondService->id]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/overview')
                ->where('summary.business_count', 2)
                ->where('summary.bookings_count', 3)
                ->has('kpis')
                ->has('funnel')
                ->has('risk_distribution')
                ->has('top_businesses', 2)
                ->has('recent_bookings', 3));
    }

    public function test_admin_cannot_trigger_staff_quick_actions(): void
    {
        $business = Business::factory()->create(['name' => 'Owner Salon']);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $booking = Booking::factory()->create([
            'business_id' => $business->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('api.bookings.check_in', ['booking' => $booking->id]))
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->postJson(route('api.bookings.mark_no_show', ['booking' => $booking->id]))
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->postJson(route('api.bookings.send_reminder', ['booking' => $booking->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_owner_can_still_trigger_staff_quick_actions(): void
    {
        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create([
            'name' => 'Owner Salon',
            'owner_user_id' => $owner->id,
        ]);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $booking = Booking::factory()->create([
            'business_id' => $business->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($owner)
            ->postJson(route('api.bookings.check_in', ['booking' => $booking->id]))
            ->assertOk();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Completed->value,
        ]);
    }
}
