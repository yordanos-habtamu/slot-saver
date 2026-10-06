<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'would_recommend' => fn (array $attributes): bool => $attributes['rating'] >= 3,
            'is_published' => true,
        ];
    }

    /**
     * A review always describes the booking it was left against.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Review $review): void {
            $booking = $review->booking()->first();

            if ($booking === null) {
                return;
            }

            $review->service_id = $booking->service_id;
            $review->business_id = $booking->business_id;
            $review->client_user_id = $booking->client_user_id;
        });
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
