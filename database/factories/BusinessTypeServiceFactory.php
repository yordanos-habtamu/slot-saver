<?php

namespace Database\Factories;

use App\Models\BusinessTypeService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BusinessTypeService>
 */
class BusinessTypeServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->word().' '.fake()->word()),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 300),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'booking_fee' => fake()->randomFloat(2, 0, 10),
            'cancellation_fee' => fake()->randomFloat(2, 0, 25),
            'free_cancellation_hours' => fake()->randomElement([12, 24, 48]),
            'max_per_client_per_day' => fake()->randomElement([1, 2, 3]),
            'is_recommended' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function recommended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recommended' => true,
        ]);
    }
}
