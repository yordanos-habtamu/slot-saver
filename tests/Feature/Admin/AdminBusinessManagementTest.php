<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_guest_is_redirected_from_business_index(): void
    {
        $this->get(route('admin.businesses.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_business_index(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('admin.businesses.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_list_with_types_and_filter_defaults(): void
    {
        Business::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.businesses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/businesses/index')
                ->has('businesses.data', 1)
                ->has('businessTypes')
                ->where('filters.search', '')
                ->where('filters.status', '')
                ->where('filters.business_type_id', null));
    }

    public function test_search_filters_by_name_city_and_owner_email(): void
    {
        Business::factory()->create([
            'name' => 'Quokka Salon',
            'city' => 'Reykjavik',
            'email' => 'quokka@salon.test',
        ]);
        $owner = User::factory()->owner()->create([
            'email' => 'wombat.owner@example.test',
        ]);
        Business::factory()->create([
            'name' => 'Wombat Spa',
            'city' => 'Oslo',
            'owner_user_id' => $owner->id,
        ]);

        $this->searchAndAssert('Quokka', 'Quokka Salon');
        $this->searchAndAssert('Reykjavik', 'Quokka Salon');
        $this->searchAndAssert('wombat.owner', 'Wombat Spa');
    }

    public function test_status_filter_selects_matching_status_only(): void
    {
        Business::factory()->create(['name' => 'Evergreen Studio']);
        Business::factory()->pending()->create(['name' => 'Lazy Studio']);

        $this->adminIndex(['status' => 'active'])
            ->assertInertia(fn (Assert $page) => $page
                ->has('businesses.data', 1)
                ->where('businesses.data.0.name', 'Evergreen Studio')
                ->where('filters.status', 'active'));

        $this->adminIndex(['status' => 'pending'])
            ->assertInertia(fn (Assert $page) => $page
                ->has('businesses.data', 1)
                ->where('businesses.data.0.name', 'Lazy Studio'));

        $this->adminIndex(['status' => 'bogus'])
            ->assertInertia(fn (Assert $page) => $page->has('businesses.data', 0));
    }

    public function test_business_type_filter_selects_matching_type_only(): void
    {
        $first = BusinessType::factory()->create();
        $second = BusinessType::factory()->create();
        Business::factory()->create(['business_type_id' => $first->id]);
        Business::factory()->create(['business_type_id' => $second->id]);

        $this->adminIndex(['business_type_id' => $first->id])
            ->assertInertia(fn (Assert $page) => $page
                ->has('businesses.data', 1)
                ->where('businesses.data.0.business_type.id', $first->id)
                ->where('filters.business_type_id', $first->id));
    }

    public function test_index_paginates_businesses(): void
    {
        Business::factory()->count(16)->create();

        $this->adminIndex()
            ->assertInertia(fn (Assert $page) => $page
                ->has('businesses.data', 15)
                ->where('businesses.total', 16)
                ->where('businesses.last_page', 2)
                ->where('businesses.current_page', 1));

        $this->adminIndex(['page' => 2])
            ->assertInertia(fn (Assert $page) => $page
                ->has('businesses.data', 1)
                ->where('businesses.current_page', 2));
    }

    public function test_admin_can_register_business_with_modal_payload(): void
    {
        $type = BusinessType::factory()->create();

        $response = $this->actingAs($this->admin)->post(
            route('admin.businesses.store'),
            $this->registrationPayload($type),
        );

        $response->assertRedirect(route('admin.businesses.index'));

        $this->assertDatabaseHas('businesses', [
            'name' => 'Nordic Barbers',
            'business_type_id' => $type->id,
            'city' => 'Oslo',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'ola@nordic.test',
            'role' => 'owner',
        ]);
        $this->assertDatabaseHas('locations', [
            'name' => 'City Center',
            'city' => 'Oslo',
            'max_capacity' => 1,
        ]);
    }

    public function test_registration_validates_required_business_fields(): void
    {
        $type = BusinessType::factory()->create();
        $payload = $this->registrationPayload($type);
        unset($payload['business']['email']);

        $this->actingAs($this->admin)
            ->post(route('admin.businesses.store'), $payload)
            ->assertSessionHasErrors('business.email');
    }

    public function test_owner_role_cannot_register_business(): void
    {
        $owner = User::factory()->owner()->create();
        $type = BusinessType::factory()->create();

        $this->actingAs($owner)
            ->post(route('admin.businesses.store'), $this->registrationPayload($type))
            ->assertForbidden();
    }

    public function test_business_detail_page_includes_analytics(): void
    {
        [$business, $location] = $this->businessWithFourBookings();

        $this->actingAs($this->admin)
            ->get(route('admin.businesses.show', $business))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/businesses/show')
                ->where('business.id', $business->id)
                ->where('business.owner.id', $business->owner_user_id)
                ->where('analytics.totals.bookings', 4)
                ->where('analytics.totals.completed', 2)
                ->where('analytics.totals.cancelled', 1)
                ->where('analytics.totals.no_show', 1)
                ->where('analytics.totals.revenue', fn ($value) => $value == 150)
                ->where('analytics.totals.cancellation_fees', fn ($value) => $value == 25)
                ->where('analytics.totals.no_show_rate', fn ($value) => $value == 25)
                ->where('analytics.totals.average_ticket', fn ($value) => $value == 75)
                ->has('analytics.weekly', 26)
                ->has('analytics.recent_bookings', 4)
                ->has('analytics.top_services', 1)
                ->where('analytics.top_services.0.bookings_count', 4)
                ->where('analytics.top_services.0.revenue', fn ($value) => $value == 150)
                ->has('locations', 1)
                ->where('locations.0.id', $location->id)
                ->where('locations.0.bookings_count', 4));
    }

    public function test_business_detail_requires_admin(): void
    {
        $this->get(route('admin.businesses.show', Business::factory()->create()))
            ->assertRedirect(route('login'));

        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('admin.businesses.show', $business))
            ->assertForbidden();
    }

    /**
     * A business with two completed bookings, one no-show and one cancellation.
     *
     * @return array{0: Business, 1: Location}
     */
    private function businessWithFourBookings(): array
    {
        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create(['owner_user_id' => $owner->id]);
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);

        $bookings = [
            ['status' => BookingStatus::Completed, 'total_amount' => 100, 'start_at' => now()->subDays(3)],
            ['status' => BookingStatus::Completed, 'total_amount' => 50, 'start_at' => now()->subDays(2)],
            ['status' => BookingStatus::NoShow, 'total_amount' => 80, 'start_at' => now()->subDay()],
            ['status' => BookingStatus::Cancelled, 'total_amount' => 60, 'cancellation_fee' => 25, 'start_at' => now()->subHours(6)],
        ];

        foreach ($bookings as $attributes) {
            Booking::factory()->create([
                ...$attributes,
                'business_id' => $business->id,
                'service_id' => $service->id,
                'location_id' => $location->id,
            ]);
        }

        return [$business, $location];
    }

    private function adminIndex(array $query = [])
    {
        return $this->actingAs($this->admin)->get(
            route('admin.businesses.index', $query),
        );
    }

    private function searchAndAssert(string $term, string $expectedName): void
    {
        $this->adminIndex(['search' => $term])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/businesses/index')
                ->has('businesses.data', 1)
                ->where('businesses.data.0.name', $expectedName)
                ->where('filters.search', $term));
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(BusinessType $type): array
    {
        return [
            'business' => [
                'business_type_id' => $type->id,
                'name' => 'Nordic Barbers',
                'email' => 'hello@nordic.test',
                'phone' => '+15550100',
                'about' => 'Premium cuts and shaves.',
                'website' => 'https://nordic.test',
                'address_line1' => '12 Main Street',
                'address_line2' => null,
                'city' => 'Oslo',
                'state' => 'Oslo',
                'country' => 'Norway',
                'postal_code' => '0150',
                'timezone' => 'Europe/Oslo',
            ],
            'owner' => [
                'name' => 'Ola Nordmann',
                'email' => 'ola@nordic.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'location' => [
                'name' => 'City Center',
                'address_line1' => '12 Main Street',
            ],
        ];
    }
}
