<?php

namespace App\Actions\Admin;

use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterBusiness
{
    public function __construct(private readonly CreateUserAccount $createUserAccount) {}

    /**
     * Register a business, its owner account and the type's default services.
     *
     * @param  array{
     *     business_type_id: int,
     *     business: array{name: string, email: string, phone?: string|null, about?: string|null, website?: string|null, address_line1: string, address_line2?: string|null, city: string, state?: string|null, country: string, postal_code?: string|null, timezone: string},
     *     owner: array{name: string, email: string, password: string},
     *     location: array{name?: string|null, address_line1?: string|null},
     * }  $attributes
     */
    public function handle(array $attributes): Business
    {
        $type = BusinessType::query()->findOrFail($attributes['business_type_id']);

        return DB::transaction(function () use ($attributes, $type): Business {
            $owner = $this->createUserAccount->handle($attributes['owner'], UserRole::Owner);

            $business = Business::create([
                ...$attributes['business'],
                'business_type_id' => $type->id,
                'owner_user_id' => $owner->id,
                'slug' => $this->uniqueSlug($attributes['business']['name']),
                'status' => BusinessStatus::Active,
            ]);

            $location = Location::create([
                'business_id' => $business->id,
                'name' => ($attributes['location']['name'] ?? null) ?: 'Main Location',
                'address_line1' => ($attributes['location']['address_line1'] ?? null) ?: $business->address_line1,
                'address_line2' => $business->address_line2,
                'city' => $business->city,
                'state' => $business->state,
                'country' => $business->country,
                'postal_code' => $business->postal_code,
                'timezone' => $business->timezone,
                'max_capacity' => 1,
            ]);

            $this->copyServiceTemplates($type, $business, $location);

            return $business;
        });
    }

    /**
     * Give the new business the service list configured for its type.
     */
    protected function copyServiceTemplates(BusinessType $type, Business $business, Location $location): void
    {
        foreach ($type->serviceTemplates as $template) {
            $service = Service::create([
                'business_id' => $business->id,
                'name' => $template->name,
                'description' => $template->description,
                'price' => $template->price,
                'duration_minutes' => $template->duration_minutes,
                'booking_fee' => $template->booking_fee,
                'cancellation_fee' => $template->cancellation_fee,
                'free_cancellation_hours' => $template->free_cancellation_hours,
                'max_per_client_per_day' => $template->max_per_client_per_day,
                'is_recommended' => $template->is_recommended,
                'sort_order' => $template->sort_order,
            ]);

            $service->locations()->attach($location, ['is_active' => true]);
        }
    }

    /**
     * Derive a unique slug from the business name.
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Business::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
