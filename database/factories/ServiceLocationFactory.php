<?php

namespace Database\Factories;

use App\Models\ServiceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLocation>
 */
class ServiceLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_override' => null,
            'duration_minutes_override' => null,
            'max_per_slot_override' => null,
            'is_active' => true,
        ];
    }
}
