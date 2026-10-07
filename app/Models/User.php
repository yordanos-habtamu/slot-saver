<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property UserRole $role
 * @property string|null $phone
 * @property string|null $avatar_path
 * @property string|null $timezone
 * @property string|null $address
 * @property string|null $preferences
 * @property string|null $rating_average
 * @property int $rating_count
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Business> $ownedBusinesses
 * @property-read Collection<int, Location> $locations
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Booking> $cancelledBookings
 * @property-read Collection<int, BookingHistory> $bookingHistory
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, Booking> $staffBookings
 */
#[Fillable(['name', 'email', 'password', 'phone', 'timezone', 'address', 'preferences'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }

    /**
     * Businesses where this user is the registered owner.
     *
     * @return HasMany<Business, $this>
     */
    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_user_id');
    }

    /**
     * Locations this user is listed as an employee on.
     *
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->withPivot('is_active')->withTimestamps();
    }

    /**
     * Services this user is able to perform.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_employee')->withTimestamps();
    }

    /**
     * Bookings this user made as a client.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_user_id');
    }

    /**
     * Bookings this user cancelled on behalf of somebody else.
     *
     * @return HasMany<Booking, $this>
     */
    public function cancelledBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'cancelled_by_user_id');
    }

    /**
     * The client's booking and cancellation ledger.
     *
     * @return HasMany<BookingHistory, $this>
     */
    public function bookingHistory(): HasMany
    {
        return $this->hasMany(BookingHistory::class, 'client_user_id');
    }

    /**
     * Reviews left by this user.
     *
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'client_user_id');
    }

    /**
     * Appointments where this user is the assigned staff member.
     *
     * @return HasMany<Booking, $this>
     */
    public function staffBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'employee_user_id');
    }

    /**
     * Recalculate the cached employee rating from published reviews
     * on appointments this user performed.
     */
    public function recalculateRatingAggregate(): void
    {
        $ratings = Review::query()
            ->join('bookings', 'reviews.booking_id', '=', 'bookings.id')
            ->where('bookings.employee_user_id', $this->id)
            ->where('reviews.is_published', true)
            ->pluck('reviews.rating');

        // Reload first so the dirty check below compares against current DB values.
        $this->refresh();

        $this->forceFill([
            'rating_count' => $ratings->count(),
            'rating_average' => $ratings->count() > 0
                ? round((float) $ratings->avg(), 2)
                : null,
        ])->save();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }
}
