<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Carbon\CarbonInterface;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $business_type_id
 * @property int|null $owner_user_id
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string|null $phone
 * @property string|null $about
 * @property string|null $logo_path
 * @property string|null $cover_image_path
 * @property string|null $website
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string|null $state
 * @property string $country
 * @property string|null $postal_code
 * @property string $timezone
 * @property BusinessStatus $status
 * @property string|null $cancellation_notice
 * @property string|null $rating_average
 * @property int $rating_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read BusinessType $businessType
 * @property-read User|null $owner
 * @property-read Collection<int, Location> $locations
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Review> $reviews
 */
#[Fillable([
    'business_type_id',
    'owner_user_id',
    'name',
    'slug',
    'email',
    'phone',
    'about',
    'logo_path',
    'cover_image_path',
    'website',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'country',
    'postal_code',
    'timezone',
    'status',
    'cancellation_notice',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BusinessType, $this>
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
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
     * Limit the query to businesses that can accept new bookings.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', BusinessStatus::Active->value);
    }
}
