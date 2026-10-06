<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'business_type_id' => BusinessType::factory(),
            'owner_user_id' => User::factory()->owner(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+1##########'),
            'about' => fake()->paragraph(),
            'logo_path' => null,
            'cover_image_path' => null,
            'website' => fake()->url(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'country' => 'United States',
            'postal_code' => fake()->postcode(),
            'timezone' => 'UTC',
            'status' => BusinessStatus::Active,
            'cancellation_notice' => fake()->sentence(),
            'rating_average' => null,
            'rating_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Pending,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Suspended,
        ]);
    }
}
