<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Data\BookingData;
use App\Domain\Booking\Exceptions\LocationClosedException;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\LocationClosure;
use App\Models\LocationOpeningHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocationScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function businessWithLocation(array $locationAttributes = []): array
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create([
            'business_id' => $business->id,
            ...$locationAttributes,
        ]);

        return [$business, $location];
    }

    private function configureWeeklyHours(Location $location, string $opens = '09:00:00', string $closes = '18:00:00'): void
    {
        foreach ([1, 2, 3, 4, 5, 6, 7] as $day) {
            LocationOpeningHour::create([
                'location_id' => $location->id,
                'day_of_week' => $day,
                'opens_at' => $opens,
                'closes_at' => $closes,
                'is_closed' => in_array($day, [1, 7], true), // closed Monday & Sunday
            ]);
        }
    }

    private function nextWeekday(int ...$isoDays): Carbon
    {
        $date = Carbon::tomorrow();

        while (! in_array($date->dayOfWeekIso, $isoDays, true)) {
            $date->addDay();
        }

        return $date;
    }

    public function test_unconfigured_location_falls_back_to_legacy_hours(): void
    {
        [, $location] = $this->businessWithLocation(['opens_at' => '10:00:00', 'closes_at' => '16:00:00']);

        $window = $location->openingWindowFor(Carbon::tomorrow());

        $this->assertSame(['opens' => '10:00', 'closes' => '16:00'], $window);
        $this->assertFalse($location->hasScheduleConfigured());
    }

    public function test_configured_schedule_closes_weekdays_without_open_hours(): void
    {
        [, $location] = $this->businessWithLocation();
        $this->configureWeeklyHours($location);

        $openDay = $this->nextWeekday(2, 3, 4, 5, 6);
        $closedDay = $this->nextWeekday(1, 7);

        $this->assertSame(['opens' => '09:00', 'closes' => '18:00'], $location->openingWindowFor($openDay));
        $this->assertNull($location->openingWindowFor($closedDay));
        $this->assertTrue($location->isOpenOn($openDay));
        $this->assertFalse($location->isOpenOn($closedDay));
    }

    public function test_date_closure_closes_the_location_for_the_covered_range(): void
    {
        [, $location] = $this->businessWithLocation();

        LocationClosure::create([
            'location_id' => $location->id,
            'starts_on' => Carbon::now()->addDays(3),
            'ends_on' => Carbon::now()->addDays(5),
            'reason' => 'Summer break',
        ]);

        $inside = Carbon::now()->addDays(4);
        $after = Carbon::now()->addDays(6);

        $this->assertNull($location->openingWindowFor($inside));
        $this->assertNotNull($location->openingWindowFor($after));

        $closure = $location->closureOn($inside);
        $this->assertNotNull($closure);
        $this->assertSame('Summer break', $closure->reason);
        $this->assertNull($location->closureOn($after));
    }

    public function test_available_slots_reports_closed_day_for_a_closure(): void
    {
        [$business, $location] = $this->businessWithLocation();
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $closedDate = Carbon::now()->addDays(3);

        LocationClosure::create([
            'location_id' => $location->id,
            'starts_on' => $closedDate,
            'ends_on' => null,
            'reason' => 'Public holiday',
        ]);

        $response = $this->getJson(route('api.booking.available_slots', [
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'date' => $closedDate->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('location_closed', true)
            ->assertJsonPath('closed_reason', 'Public holiday')
            ->assertJsonPath('available_slots', []);
    }

    public function test_available_slots_respect_configured_opening_window(): void
    {
        [$business, $location] = $this->businessWithLocation();
        $this->configureWeeklyHours($location, '11:00:00', '14:00:00');
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $openDay = $this->nextWeekday(2, 3, 4, 5, 6);

        $response = $this->getJson(route('api.booking.available_slots', [
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'date' => $openDay->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('location_closed', false)
            ->assertJsonPath('opens_at', '11:00')
            ->assertJsonPath('closes_at', '14:00');

        $slots = collect($response->json('available_slots'));
        $this->assertNotEmpty($slots);
        $this->assertSame('11:00', $slots->first()['time']);
        $this->assertSame('14:00', Carbon::parse($slots->last()['end_at'])->format('H:i'));
    }

    public function test_available_slots_reports_closed_on_configured_closed_weekday(): void
    {
        [$business, $location] = $this->businessWithLocation();
        $this->configureWeeklyHours($location);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $closedDay = $this->nextWeekday(1, 7);

        $response = $this->getJson(route('api.booking.available_slots', [
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'date' => $closedDay->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('location_closed', true)
            ->assertJsonPath('available_slots', []);
    }

    public function test_create_booking_rejects_closure_day_and_closed_weekday(): void
    {
        [$business, $location] = $this->businessWithLocation();
        $this->configureWeeklyHours($location);
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 30]);
        $client = User::factory()->client()->create();

        $closedDay = $this->nextWeekday(1, 7);

        try {
            (new CreateBooking)->execute(new BookingData(
                businessId: $business->id,
                locationId: $location->id,
                serviceId: $service->id,
                clientUserId: $client->id,
                employeeUserId: null,
                startAt: $closedDay->setTime(11, 0),
            ));
            $this->fail('Expected LocationClosedException on a configured closed weekday.');
        } catch (LocationClosedException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $closureDay = Carbon::now()->addDays(8);
        LocationClosure::create([
            'location_id' => $location->id,
            'starts_on' => $closureDay,
            'ends_on' => null,
            'reason' => 'Owner vacation',
        ]);

        $this->expectException(LocationClosedException::class);

        (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: null,
            startAt: $closureDay->setTime(11, 0),
        ));
    }

    public function test_create_booking_rejects_appointment_outside_operating_hours(): void
    {
        [$business, $location] = $this->businessWithLocation();
        $this->configureWeeklyHours($location, '09:00:00', '18:00:00');
        $service = Service::factory()->create(['business_id' => $business->id, 'duration_minutes' => 60]);
        $client = User::factory()->client()->create();

        // 17:30 + 60 minutes ends at 18:30 — past closing time
        $openDay = $this->nextWeekday(2, 3, 4, 5, 6);

        $this->expectException(LocationClosedException::class);

        (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: null,
            startAt: $openDay->setTime(17, 30),
        ));
    }

    public function test_create_booking_enforces_location_capacity_without_employee(): void
    {
        [$business, $location] = $this->businessWithLocation(['max_capacity' => 1]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duration_minutes' => 30,
            'max_per_slot' => null,
        ]);
        $clients = User::factory()->count(2)->client()->create();
        $startAt = Carbon::now()->addDays(2)->setTime(14, 0);

        (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $clients->first()->id,
            employeeUserId: null,
            startAt: $startAt,
        ));

        $this->expectException(SlotAlreadyBookedException::class);

        (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $clients->last()->id,
            employeeUserId: null,
            startAt: $startAt->copy()->addMinutes(15),
        ));
    }

    public function test_service_max_per_slot_overrides_location_capacity(): void
    {
        [$business, $location] = $this->businessWithLocation(['max_capacity' => 1]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duration_minutes' => 30,
            'max_per_slot' => 2,
        ]);
        $clients = User::factory()->count(2)->client()->create();
        $startAt = Carbon::now()->addDays(2)->setTime(14, 0);

        (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $clients->first()->id,
            employeeUserId: null,
            startAt: $startAt,
        ));

        // Second concurrent appointment allowed because max_per_slot = 2
        $second = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $clients->last()->id,
            employeeUserId: null,
            startAt: $startAt->copy(),
        ));

        $this->assertNotNull($second->id);
        $this->assertSame(BookingStatus::Confirmed, $second->status);
    }

    public function test_available_slots_expose_seats_left_within_capacity(): void
    {
        [$business, $location] = $this->businessWithLocation(['max_capacity' => 2]);
        $service = Service::factory()->create([
            'business_id' => $business->id,
            'duration_minutes' => 30,
            'max_per_slot' => null,
        ]);
        $client = User::factory()->client()->create();
        $targetDate = Carbon::now()->addDays(3);

        Booking::factory()->create([
            'client_user_id' => $client->id,
            'service_id' => $service->id,
            'business_id' => $business->id,
            'location_id' => $location->id,
            'employee_user_id' => null,
            'status' => BookingStatus::Confirmed,
            'start_at' => $targetDate->setTime(10, 0),
            'end_at' => $targetDate->setTime(10, 30),
            'duration_minutes' => 30,
        ]);

        $response = $this->getJson(route('api.booking.available_slots', [
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'date' => $targetDate->toDateString(),
        ]));

        $response->assertOk()->assertJsonPath('capacity', 2);

        $slots = collect($response->json('available_slots'));
        $tenOclock = $slots->firstWhere('time', '10:00');

        // One of two seats taken at 10:00 — still bookable with a seat to spare
        $this->assertNotNull($tenOclock);
        $this->assertSame(1, $tenOclock['seats_left']);
    }
}
