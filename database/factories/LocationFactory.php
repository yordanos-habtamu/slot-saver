<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->city().' Branch',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'country' => 'United States',
            'postal_code' => fake()->postcode(),
            'timezone' => 'UTC',
            'phone' => fake()->numerify('+1##########'),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'max_capacity' => fake()->numberBetween(1, 8),
            'max_bookings_per_day' => fake()->numberBetween(10, 60),
            'opens_at' => '09:00:00',
            'closes_at' => '18:00:00',
            'is_active' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
