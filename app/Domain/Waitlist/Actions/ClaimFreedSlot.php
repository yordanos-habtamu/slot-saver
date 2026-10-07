<?php

namespace App\Domain\Waitlist\Actions;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Data\BookingData;
use App\Models\Booking;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClaimFreedSlot
{
    public function __construct(
        protected CreateBooking $createBooking
    ) {}

    /**
     * Claim an offered waitlist slot before the 15-minute countdown expires.
     *
     * @return array{booking: Booking, waitlist_entry: WaitlistEntry}
     */
    public function execute(string $token): array
    {
        $entry = WaitlistEntry::where('claim_token', $token)->first();

        if (! $entry) {
            throw new InvalidArgumentException('Invalid or unknown waitlist claim token.');
        }

        if (! $entry->isClaimable()) {
            throw new InvalidArgumentException('This waitlist offer has expired and is no longer claimable.');
        }

        $freedBooking = $entry->freedBooking;

        if (! $freedBooking) {
            throw new InvalidArgumentException('Original booking for this slot could not be resolved.');
        }

        return DB::transaction(function () use ($entry, $freedBooking) {
            // Create confirmed booking for the waitlist client
            $newBooking = $this->createBooking->execute(new BookingData(
                businessId: $entry->business_id,
                locationId: $freedBooking->location_id,
                serviceId: $entry->service_id,
                clientUserId: $entry->client_user_id,
                employeeUserId: $entry->preferred_employee_id ?? $freedBooking->employee_user_id,
                startAt: $freedBooking->start_at,
                clientNote: 'Booked via instant waitlist auto-fill claim.',
                depositStatus: 'none',
            ));

            $entry->update([
                'status' => 'claimed',
            ]);

            return [
                'booking' => $newBooking,
                'waitlist_entry' => $entry,
            ];
        });
    }
}
