<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Data\BookingData;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Enums\BookingStatus;
use App\Enums\HistoryAction;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Location;
use App\Models\Reminder;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    /**
     * Create an appointment with race-condition resilience and reminder scheduling.
     *
     * @throws SlotAlreadyBookedException
     */
    public function execute(BookingData $data): Booking
    {
        $service = Service::with(['locations', 'serviceLocations'])->findOrFail($data->serviceId);
        $location = Location::findOrFail($data->locationId);

        $duration = $service->durationFor($location);
        $price = $service->priceFor($location);
        $bookingFee = (float) $service->booking_fee;
        $totalAmount = (float) $price + $bookingFee;

        $endAt = $data->startAt->copy()->addMinutes($duration);

        // Application-level guard: employee conflict detection
        $hasConflict = Booking::query()
            ->where('employee_user_id', $data->employeeUserId)
            ->overlapping($data->startAt, $endAt)
            ->exists();

        if ($hasConflict) {
            throw new SlotAlreadyBookedException('The selected employee is not available for this time window.');
        }

        $status = $data->depositAmount > 0 && $data->depositStatus !== 'paid'
            ? BookingStatus::Pending
            : BookingStatus::Confirmed;

        return DB::transaction(function () use ($data, $service, $location, $duration, $price, $bookingFee, $totalAmount, $endAt, $status) {
            try {
                $booking = Booking::create([
                    'reference_code' => Booking::generateReferenceCode(),
                    'client_user_id' => $data->clientUserId,
                    'business_id' => $data->businessId,
                    'location_id' => $data->locationId,
                    'service_id' => $data->serviceId,
                    'employee_user_id' => $data->employeeUserId,
                    'status' => $status,
                    'start_at' => $data->startAt,
                    'end_at' => $endAt,
                    'duration_minutes' => $duration,
                    'buffer_minutes' => 10,
                    'party_size' => $data->partySize,
                    'client_note' => $data->clientNote,
                    'service_price' => $price,
                    'booking_fee' => $bookingFee,
                    'total_amount' => $totalAmount,
                    'currency' => 'USD',
                    'deposit_amount' => $data->depositAmount ?? 0.0,
                    'deposit_status' => $data->depositStatus ?? 'none',
                    'deposit_paid_at' => $data->depositStatus === 'paid' ? now() : null,
                    'risk_score' => $data->riskScore,
                    'risk_tier' => $data->riskTier,
                ]);

                // Record audit ledger
                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'client_user_id' => $data->clientUserId,
                    'action' => HistoryAction::Booked,
                    'business_name' => $booking->business->name,
                    'location_name' => $location->name,
                    'service_name' => $service->name,
                    'scheduled_start_at' => $data->startAt,
                    'scheduled_end_at' => $endAt,
                    'amount' => $totalAmount,
                    'note' => 'Appointment booked via platform.',
                ]);

                // Schedule automated reminders
                $this->scheduleDefaultReminders($booking);

                return $booking;
            } catch (QueryException $e) {
                // Catch PostgreSQL 23P01 (exclusion_violation)
                if ($e->getCode() === '23P01' || str_contains($e->getMessage(), 'no_overlapping_staff_bookings')) {
                    throw new SlotAlreadyBookedException('The requested time slot was just booked by another customer. Please select another slot.');
                }

                throw $e;
            }
        });
    }

    /**
     * Schedule 48h, 24h, and 2h reminder checkpoints if they land in the future.
     */
    protected function scheduleDefaultReminders(Booking $booking): void
    {
        $intervals = [
            '48h' => $booking->start_at->copy()->subHours(48),
            '24h' => $booking->start_at->copy()->subHours(24),
            '2h' => $booking->start_at->copy()->subHours(2),
        ];

        foreach ($intervals as $type => $scheduledFor) {
            if ($scheduledFor->isFuture()) {
                Reminder::create([
                    'booking_id' => $booking->id,
                    'channel' => 'whatsapp',
                    'type' => $type,
                    'scheduled_for' => $scheduledFor,
                    'delivery_status' => 'scheduled',
                ]);
            }
        }
    }
}
