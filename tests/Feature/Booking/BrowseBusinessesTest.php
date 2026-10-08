<?php

namespace Tests\Feature\Booking;

use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrowseBusinessesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_all_active_businesses_with_their_services(): void
    {
        $first = Business::factory()->create(['name' => 'Aurora Hair Studio', 'slug' => 'aurora-hair']);
        $second = Business::factory()->create(['name' => 'Crown & Blade Barbershop', 'slug' => 'crown-and-blade']);

        Service::factory()->create(['business_id' => $first->id, 'name' => 'Hair Cut & Style', 'price' => 45]);
        Service::factory()->create(['business_id' => $second->id, 'name' => 'Skin Fade', 'price' => 30]);
        Location::factory()->create(['business_id' => $first->id]);

        $this->get(route('booking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('booking/browse')
                ->has('businesses', 2)
                ->where('businesses.0.name', 'Aurora Hair Studio')
                ->where('businesses.1.name', 'Crown & Blade Barbershop')
                ->where('businesses.0.services.0.name', 'Hair Cut & Style')
                ->where('businesses.0.location_count', 1));
    }

    public function test_inactive_businesses_are_not_listed(): void
    {
        Business::factory()->create(['name' => 'Active Salon']);
        Business::factory()->pending()->create(['name' => 'Pending Salon']);

        $this->get(route('booking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('booking/browse')
                ->has('businesses', 1)
                ->where('businesses.0.name', 'Active Salon'));
    }

    public function test_slug_url_renders_the_booking_wizard(): void
    {
        $business = Business::factory()->create(['name' => 'Aurora Hair Studio', 'slug' => 'aurora-hair']);

        $this->get(route('booking.index', ['slug' => $business->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('booking/index')
                ->where('business.slug', $business->slug));
    }
}
