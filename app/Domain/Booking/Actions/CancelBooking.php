<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Events\BookingCancelledEvent;
use App\Enums\BookingStatus;
use App\Enums\HistoryAction;
use App\Models\Booking;
use App\Models\BookingHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelBooking
{
    /**
     * Cancel an appointment, calculate any applicable cancellation fees,
     * cancel pending reminders, and fire event for waitlist auto-fill.
     */
    public function execute(Booking $booking, ?int $cancelledByUserId = null, ?string $reason = null): Booking
    {
        if ($booking->status->isFinal()) {
            throw new InvalidArgumentException("Booking #{$booking->reference_code} is already {$booking->status->value} and cannot be cancelled.");
        }

        return DB::transaction(function () use ($booking, $cancelledByUserId, $reason) {
            $now = now();
            $feeDue = $booking->cancellationFeeDue($now);

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => $now,
                'cancelled_by_user_id' => $cancelledByUserId ?? $booking->client_user_id,
                'cancellation_reason' => $reason ?? 'Cancelled by customer.',
                'cancellation_fee' => $feeDue,
            ]);

            // Record audit ledger
            BookingHistory::create([
                'booking_id' => $booking->id,
                'client_user_id' => $booking->client_user_id,
                'action' => HistoryAction::Cancelled,
                'business_name' => $booking->business->name,
                'location_name' => $booking->location->name,
                'service_name' => $booking->service->name,
                'scheduled_start_at' => $booking->start_at,
                'scheduled_end_at' => $booking->end_at,
                'amount' => $feeDue,
                'note' => $reason ?? 'Appointment cancelled.',
            ]);

            // Cancel any pending scheduled reminders
            $booking->reminders()
                ->where('delivery_status', 'scheduled')
                ->update(['delivery_status' => 'cancelled']);

            // Fire event so waitlist auto-fill engine can offer slot to candidates
            BookingCancelledEvent::dispatch($booking, $reason);

            return $booking;
        });
    }
}
