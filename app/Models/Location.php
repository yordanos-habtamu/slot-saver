<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string|null $state
 * @property string $country
 * @property string|null $postal_code
 * @property string $timezone
 * @property string|null $phone
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int $max_capacity
 * @property int|null $max_bookings_per_day
 * @property string|null $opens_at
 * @property string|null $closes_at
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Business $business
 * @property-read Collection<int, User> $employees
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, Booking> $bookings
 */
#[Fillable([
    'business_id',
    'name',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'country',
    'postal_code',
    'timezone',
    'phone',
    'latitude',
    'longitude',
    'max_capacity',
    'max_bookings_per_day',
    'opens_at',
    'closes_at',
    'is_active',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:5',
            'longitude' => 'decimal:5',
            'max_capacity' => 'integer',
            'max_bookings_per_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The employee list for this location.
     *
     * @return BelongsToMany<User, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Services offered at this location.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_location')
            ->withPivot(['price_override', 'duration_minutes_override', 'max_per_slot_override', 'is_active'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ServiceLocation, $this>
     */
    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Limit the query to locations open for booking.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
