<?php

namespace Database\Factories;

use App\Enums\HistoryAction;
use App\Models\Booking;
use App\Models\BookingHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingHistory>
 */
class BookingHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'action' => HistoryAction::Booked,
            'amount' => fake()->randomFloat(2, 0, 25),
            'currency' => 'USD',
            'note' => null,
        ];
    }

    /**
     * The ledger row is a snapshot, so it must not read from live relations.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (BookingHistory $history): void {
            $booking = $history->booking()->first();

            if (! $booking instanceof Booking) {
                return;
            }

            $history->client_user_id = $booking->client_user_id;
            $history->business_id = $booking->business_id;
            $history->service_id = $booking->service_id;
            $history->business_name = $booking->business->name;
            $history->service_name = $booking->service->name;
            $history->location_name = $booking->location->name;
            $history->scheduled_start_at = $booking->start_at;
            $history->scheduled_end_at = $booking->end_at;
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => HistoryAction::Cancelled,
        ]);
    }

    public function rescheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => HistoryAction::Rescheduled,
        ]);
    }
}
