<?php

namespace App\Domain\Reminders\Actions;

use App\Domain\Booking\Actions\CancelBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Reminder;
use Illuminate\Support\Str;

class HandleInteractiveButtonReply
{
    public function __construct(
        protected CancelBooking $cancelBooking
    ) {}

    /**
     * Handle inbound interactive WhatsApp reply (confirm, reschedule, cancel).
     *
     * @return array{action: string, booking_id: int|null, success: bool, message: string}
     */
    public function execute(string $buttonId, ?string $senderPhone = null): array
    {
        // Parse format e.g. "confirm_SS-ABC12345"
        $parts = explode('_', $buttonId, 2);
        $action = Str::lower($parts[0]);
        $reference = $parts[1] ?? null;

        if (! $reference) {
            return [
                'action' => $action,
                'booking_id' => null,
                'success' => false,
                'message' => 'Missing booking reference in button payload.',
            ];
        }

        $booking = Booking::where('reference_code', $reference)->first();

        if (! $booking) {
            return [
                'action' => $action,
                'booking_id' => null,
                'success' => false,
                'message' => "Booking with reference {$reference} not found.",
            ];
        }

        // Record on latest reminder if exists
        $reminder = $booking->reminders()->latest()->first();

        switch ($action) {
            case 'confirm':
                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => now(),
                ]);

                if ($reminder) {
                    $reminder->update(['interactive_action' => 'confirm']);
                }

                return [
                    'action' => 'confirm',
                    'booking_id' => $booking->id,
                    'success' => true,
                    'message' => 'Booking confirmed successfully.',
                ];

            case 'cancel':
                $this->cancelBooking->execute($booking, $booking->client_user_id, 'Cancelled via WhatsApp one-tap reminder.');

                if ($reminder) {
                    $reminder->update(['interactive_action' => 'cancel']);
                }

                return [
                    'action' => 'cancel',
                    'booking_id' => $booking->id,
                    'success' => true,
                    'message' => 'Booking cancelled and waitlist refill initiated.',
                ];

            case 'reschedule':
                if ($reminder) {
                    $reminder->update(['interactive_action' => 'reschedule']);
                }

                return [
                    'action' => 'reschedule',
                    'booking_id' => $booking->id,
                    'success' => true,
                    'message' => 'Reschedule requested.',
                ];

            default:
                return [
                    'action' => $action,
                    'booking_id' => $booking->id,
                    'success' => false,
                    'message' => "Unrecognized action: {$action}",
                ];
        }
    }
}
