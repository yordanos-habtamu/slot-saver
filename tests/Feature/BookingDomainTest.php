<?php

namespace Tests\Feature;

use App\Enums\HistoryAction;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Business;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_booking_is_always_tied_to_its_service_business_and_a_location_of_that_business(): void
    {
        $booking = Booking::factory()->create();

        $this->assertSame($booking->service->business_id, $booking->business_id);
        $this->assertSame($booking->business_id, $booking->location->business_id);
    }

    public function test_the_ledger_snapshots_the_business_service_and_location_names(): void
    {
        $booking = Booking::factory()->create();

        $entry = BookingHistory::factory()->create(['booking_id' => $booking->id]);

        $this->assertSame($booking->client_user_id, $entry->client_user_id);
        $this->assertSame($booking->business->name, $entry->business_name);
        $this->assertSame($booking->service->name, $entry->service_name);
        $this->assertSame($booking->location->name, $entry->location_name);
        $this->assertTrue($entry->scheduled_start_at->equalTo($booking->start_at));
        $this->assertTrue($entry->scheduled_end_at->equalTo($booking->end_at));
        $this->assertSame(HistoryAction::Booked, $entry->action);
    }

    public function test_a_client_only_has_one_review_per_booking(): void
    {
        $booking = Booking::factory()->completed()->create();

        Review::factory()->create(['booking_id' => $booking->id]);

        $this->expectException(QueryException::class);

        Review::factory()->create(['booking_id' => $booking->id]);
    }

    public function test_service_locations_carry_their_own_price_and_duration_overrides(): void
    {
        $service = Service::factory()->create(['price' => 100, 'duration_minutes' => 30]);
        $location = Location::factory()->create(['business_id' => $service->business_id]);

        $service->locations()->attach($location, [
            'price_override' => 85,
            'duration_minutes_override' => 45,
        ]);

        $service = $service->fresh('locations', 'serviceLocations');

        $this->assertSame('85.00', $service->priceFor($location));
        $this->assertSame(45, $service->durationFor($location));
        $this->assertSame('100.00', $service->price);
        $this->assertSame(30, $service->duration_minutes);
    }

    public function test_a_location_only_lists_an_employee_once(): void
    {
        $location = Location::factory()->create();
        $employee = User::factory()->employee()->create();

        $location->employees()->attach($employee);

        $this->expectException(QueryException::class);

        $location->employees()->attach($employee);
    }

    public function test_rating_aggregates_are_recalculated_from_published_reviews(): void
    {
        $service = Service::factory()->create();

        Review::factory()->create([
            'booking_id' => Booking::factory()->completed()->create(['service_id' => $service->id])->id,
            'service_id' => $service->id,
            'rating' => 5,
        ]);

        Review::factory()->unpublished()->create([
            'booking_id' => Booking::factory()->completed()->create(['service_id' => $service->id])->id,
            'service_id' => $service->id,
            'rating' => 1,
        ]);

        $service->recalculateRatings();

        $this->assertSame('5.00', $service->rating_average);
        $this->assertSame(1, $service->rating_count);
        $this->assertSame('100.00', $service->recommendation_percentage);
    }

    public function test_the_admin_owns_the_platform_while_a_business_has_a_single_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create(['owner_user_id' => $owner->id]);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->role->isStaff());
        $this->assertFalse($admin->isClient());
        $this->assertSame(UserRole::Owner, $owner->role);
        $this->assertTrue($owner->role->isStaff());
        $this->assertSame($business->id, $owner->ownedBusinesses->first()->id);
    }

    public function test_a_deleting_a_business_type_in_use_is_blocked(): void
    {
        $business = Business::factory()->create();

        $this->expectException(QueryException::class);

        $business->businessType->delete();
    }
}
