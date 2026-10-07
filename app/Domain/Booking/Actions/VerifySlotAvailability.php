<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Exceptions\LocationClosedException;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Service;
use Carbon\CarbonInterface;

class VerifySlotAvailability
{
    /**
     * Verify that a slot is bookable: the location must be open (schedule,
     * closures, active flag), the assigned employee must be free, and the
     * location must not be at capacity for that window.
     *
     *
     * @param  int|null  $ignoreBookingId  Booking to exclude from conflict checks (reschedules).
     *
     * @throws LocationClosedException
     * @throws SlotAlreadyBookedException
     */
    public function execute(
        Location $location,
        Service $service,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $employeeUserId = null,
        ?int $ignoreBookingId = null,
    ): void {
        $this->assertLocationIsOpen($location, $startAt, $endAt);

        if ($employeeUserId !== null) {
            $hasConflict = Booking::query()
                ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
                ->where('employee_user_id', $employeeUserId)
                ->occupying()
                ->overlapping($startAt, $endAt)
                ->exists();

            if ($hasConflict) {
                throw new SlotAlreadyBookedException('The selected employee is not available for this time window.');
            }
        }

        $capacity = $this->capacityFor($location, $service);

        $seatsTaken = Booking::query()
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->where('location_id', $location->id)
            ->occupying()
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->count();

        if ($seatsTaken >= $capacity) {
            throw new SlotAlreadyBookedException(
                "This time slot is fully booked (capacity {$capacity} concurrent appointment(s)). Please pick another time."
            );
        }
    }

    /**
     * How many appointments can run at this location at the same time.
     */
    public function capacityFor(Location $location, Service $service): int
    {
        return $service->maxPerSlotFor($location) ?? $location->max_capacity ?? 1;
    }

    /**
     * Guard the date/time window against closures and configured opening hours.
     *
     * @throws LocationClosedException
     */
    protected function assertLocationIsOpen(Location $location, CarbonInterface $startAt, CarbonInterface $endAt): void
    {
        if (! $location->is_active) {
            throw new LocationClosedException('This location is not accepting bookings right now.');
        }

        $closure = $location->closureOn($startAt);

        if ($closure !== null) {
            throw new LocationClosedException($closure->label());
        }

        // Only enforce weekly hours when the owner has configured a schedule;
        // unconfigured locations keep the legacy behaviour.
        if (! $location->hasScheduleConfigured()) {
            return;
        }

        $window = $location->openingWindowFor($startAt);

        if ($window === null) {
            throw new LocationClosedException('This location is closed on the selected day. Please pick another date.');
        }

        if ($startAt->format('H:i') < $window['opens'] || $endAt->format('H:i') > $window['closes']) {
            throw new LocationClosedException(
                "This location is open {$window['opens']}–{$window['closes']} on the selected day. The requested appointment falls outside operating hours."
            );
        }
    }
}
