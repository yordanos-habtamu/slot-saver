<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ServiceFactory;
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
 * @property string|null $description
 * @property string|null $category
 * @property string $price
 * @property int $duration_minutes
 * @property string $booking_fee
 * @property string $cancellation_fee
 * @property int|null $free_cancellation_hours
 * @property int|null $max_per_slot
 * @property int|null $max_per_client_per_day
 * @property string|null $image_path
 * @property bool $is_active
 * @property bool $is_recommended
 * @property int $sort_order
 * @property string|null $rating_average
 * @property int $rating_count
 * @property string|null $recommendation_percentage
 * @property int $times_booked
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Business $business
 * @property-read Collection<int, Location> $locations
 * @property-read Collection<int, ServiceLocation> $serviceLocations
 * @property-read Collection<int, User> $employees
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Review> $reviews
 */
#[Fillable([
    'business_id',
    'name',
    'description',
    'category',
    'price',
    'duration_minutes',
    'booking_fee',
    'cancellation_fee',
    'free_cancellation_hours',
    'max_per_slot',
    'max_per_client_per_day',
    'image_path',
    'is_active',
    'is_recommended',
    'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'booking_fee' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
            'rating_average' => 'decimal:2',
            'recommendation_percentage' => 'decimal:2',
            'duration_minutes' => 'integer',
            'free_cancellation_hours' => 'integer',
            'max_per_slot' => 'integer',
            'max_per_client_per_day' => 'integer',
            'sort_order' => 'integer',
            'rating_count' => 'integer',
            'times_booked' => 'integer',
            'is_active' => 'boolean',
            'is_recommended' => 'boolean',
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
     * Locations offering this service.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'service_location')
            ->withPivot(['price_override', 'duration_minutes_override', 'max_per_slot_override', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Per-location overrides for this service.
     *
     * @return HasMany<ServiceLocation, $this>
     */
    public function serviceLocations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    /**
     * Employees cleared to perform this service.
     *
     * @return BelongsToMany<User, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_employee')->withTimestamps();
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Limit the query to services bookable right now.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Limit the query to the services the platform highlights.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function recommended(Builder $query): void
    {
        $query->where('is_active', true)->where('is_recommended', true);
    }

    /**
     * The price charged at the given location, honouring location overrides.
     */
    public function priceFor(Location $location): string
    {
        $override = $this->serviceLocations
            ->firstWhere('location_id', $location->id)?->price_override;

        return $override ?? $this->price;
    }

    /**
     * The duration booked at the given location, honouring location overrides.
     */
    public function durationFor(Location $location): int
    {
        $override = $this->serviceLocations
            ->firstWhere('location_id', $location->id)?->duration_minutes_override;

        return $override ?? $this->duration_minutes;
    }

    /**
     * The slot cap at the given location, honouring location overrides.
     */
    public function maxPerSlotFor(Location $location): ?int
    {
        $override = $this->serviceLocations
            ->firstWhere('location_id', $location->id)?->max_per_slot_override;

        return $override ?? $this->max_per_slot;
    }

    /**
     * Recalculate the cached rating aggregates from published reviews.
     */
    public function recalculateRatings(): void
    {
        $reviews = $this->reviews()->where('is_published', true)->get(['rating', 'would_recommend']);

        $this->forceFill([
            'rating_count' => $reviews->count(),
            'rating_average' => $reviews->count() > 0
                ? round((float) $reviews->avg('rating'), 2)
                : null,
            'recommendation_percentage' => $reviews->count() > 0
                ? round((float) $reviews->avg(fn (Review $review): int => $review->would_recommend ? 1 : 0) * 100, 2)
                : null,
        ])->save();
    }
}
