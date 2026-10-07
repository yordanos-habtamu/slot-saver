<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Enums\BookingStatus;
use App\Enums\HistoryAction;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Reminder;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RescheduleBooking
{
    /**
     * Reschedule an appointment to a new time window with exclusion checks and reminder resetting.
     *
     * @throws SlotAlreadyBookedException
     */
    public function execute(Booking $booking, CarbonInterface $newStartAt, ?int $newEmployeeId = null): Booking
    {
        if ($booking->status->isFinal()) {
            throw new InvalidArgumentException("Cannot reschedule booking #{$booking->reference_code} with status {$booking->status->value}.");
        }

        $employeeId = $newEmployeeId ?? $booking->employee_user_id;
        $newEndAt = $newStartAt->copy()->addMinutes($booking->duration_minutes);

        // Pre-check employee conflict detection
        $hasConflict = Booking::query()
            ->where('employee_user_id', $employeeId)
            ->whereKeyNot($booking->id)
            ->overlapping($newStartAt, $newEndAt)
            ->exists();

        if ($hasConflict) {
            throw new SlotAlreadyBookedException('The selected employee is not available for this time window.');
        }

        return DB::transaction(function () use ($booking, $newStartAt, $newEndAt, $employeeId) {
            try {
                $booking->update([
                    'start_at' => $newStartAt,
                    'end_at' => $newEndAt,
                    'employee_user_id' => $employeeId,
                    'status' => BookingStatus::Confirmed,
                ]);

                // Record audit ledger
                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'client_user_id' => $booking->client_user_id,
                    'action' => HistoryAction::Rescheduled,
                    'business_name' => $booking->business->name,
                    'location_name' => $booking->location->name,
                    'service_name' => $booking->service->name,
                    'scheduled_start_at' => $newStartAt,
                    'scheduled_end_at' => $newEndAt,
                    'amount' => $booking->total_amount,
                    'note' => 'Appointment rescheduled.',
                ]);

                // Reset scheduled reminders
                $booking->reminders()->where('delivery_status', 'scheduled')->delete();

                $intervals = [
                    '48h' => $newStartAt->copy()->subHours(48),
                    '24h' => $newStartAt->copy()->subHours(24),
                    '2h' => $newStartAt->copy()->subHours(2),
                ];

                foreach ($intervals as $type => $time) {
                    if ($time->isFuture()) {
                        Reminder::create([
                            'booking_id' => $booking->id,
                            'channel' => 'whatsapp',
                            'type' => $type,
                            'scheduled_for' => $time,
                            'delivery_status' => 'scheduled',
                        ]);
                    }
                }

                return $booking;
            } catch (QueryException $e) {
                if ($e->getCode() === '23P01' || str_contains($e->getMessage(), 'no_overlapping_staff_bookings')) {
                    throw new SlotAlreadyBookedException('The new time slot conflicts with an existing booking.');
                }

                throw $e;
            }
        });
    }
}
