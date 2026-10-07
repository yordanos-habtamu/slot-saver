<?php

namespace App\Domain\Booking\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarkNoShow
{
    /**
     * Mark an appointment as a no-show, forfeiting any paid deposit.
     */
    public function execute(Booking $booking, ?string $note = null): Booking
    {
        if ($booking->status->isFinal()) {
            throw new InvalidArgumentException("Booking #{$booking->reference_code} is already final ({$booking->status->value}).");
        }

        return DB::transaction(function () use ($booking, $note) {
            $depositStatus = $booking->deposit_status === 'paid' ? 'forfeited' : $booking->deposit_status;

            $booking->update([
                'status' => BookingStatus::NoShow,
                'completed_at' => now(),
                'deposit_status' => $depositStatus,
                'internal_note' => $note ?? $booking->internal_note,
            ]);

            // Cancel any remaining reminders
            $booking->reminders()
                ->where('delivery_status', 'scheduled')
                ->update(['delivery_status' => 'cancelled']);

            return $booking;
        });
    }
}
