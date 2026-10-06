<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => Str::title(fake()->unique()->word().' '.fake()->word()),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Standard', 'Premium', 'Express', 'Add-on']),
            'price' => fake()->randomFloat(2, 10, 300),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'booking_fee' => fake()->randomFloat(2, 0, 10),
            'cancellation_fee' => fake()->randomFloat(2, 0, 25),
            'free_cancellation_hours' => fake()->randomElement([12, 24, 48]),
            'max_per_slot' => fake()->numberBetween(1, 5),
            'max_per_client_per_day' => fake()->randomElement([1, 2, 3]),
            'image_path' => null,
            'is_active' => true,
            'is_recommended' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 20),
            'rating_average' => null,
            'rating_count' => 0,
            'recommendation_percentage' => null,
            'times_booked' => 0,
        ];
    }

    public function recommended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recommended' => true,
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
