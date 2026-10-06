<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::now()->addDay()->setTime(fake()->numberBetween(9, 16), 0);
        $price = fake()->randomFloat(2, 10, 300);
        $bookingFee = fake()->randomFloat(2, 0, 10);

        return [
            'reference_code' => Booking::generateReferenceCode(),
            'client_user_id' => User::factory()->client(),
            'service_id' => Service::factory(),
            'business_id' => null,
            'location_id' => null,
            'employee_user_id' => null,
            'status' => BookingStatus::Confirmed,
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'party_size' => 1,
            'client_note' => null,
            'internal_note' => null,
            'service_price' => $price,
            'booking_fee' => $bookingFee,
            'total_amount' => $price + $bookingFee,
            'currency' => 'USD',
            'cancellation_fee' => null,
            'cancelled_at' => null,
            'cancelled_by_user_id' => null,
            'cancellation_reason' => null,
            'confirmed_at' => now(),
            'completed_at' => null,
        ];
    }

    /**
     * A booking always points at a service, the business that owns it and a
     * location of that same business.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Booking $booking): void {
            if ($booking->getAttribute('business_id') === null) {
                $service = $booking->service()->first();

                if ($service === null) {
                    throw new LogicException('A booking cannot be created without a service.');
                }

                $booking->business_id = $service->business_id;
            }

            if ($booking->getAttribute('location_id') === null) {
                $booking->location_id = Location::factory()
                    ->create(['business_id' => $booking->business_id])
                    ->id;
            }
        });
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Pending,
            'confirmed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_fee' => fake()->randomFloat(2, 0, 25),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::NoShow,
            'completed_at' => now(),
        ]);
    }
}
