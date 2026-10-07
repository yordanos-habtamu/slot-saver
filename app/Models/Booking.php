<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference_code
 * @property int $client_user_id
 * @property int $business_id
 * @property int $location_id
 * @property int $service_id
 * @property int|null $employee_user_id
 * @property BookingStatus $status
 * @property CarbonInterface $start_at
 * @property CarbonInterface $end_at
 * @property int $duration_minutes
 * @property int $party_size
 * @property string|null $client_note
 * @property string|null $internal_note
 * @property string $service_price
 * @property string $booking_fee
 * @property string $total_amount
 * @property string $currency
 * @property string|null $cancellation_fee
 * @property CarbonInterface|null $cancelled_at
 * @property int|null $cancelled_by_user_id
 * @property string|null $cancellation_reason
 * @property CarbonInterface|null $confirmed_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $client
 * @property-read Business $business
 * @property-read Location $location
 * @property-read Service $service
 * @property-read User|null $employee
 * @property-read User|null $cancelledBy
 * @property-read Collection<int, BookingHistory> $history
 * @property-read Review|null $review
 */
#[Fillable([
    'reference_code',
    'client_user_id',
    'business_id',
    'location_id',
    'service_id',
    'employee_user_id',
    'status',
    'start_at',
    'end_at',
    'duration_minutes',
    'buffer_minutes',
    'party_size',
    'client_note',
    'internal_note',
    'service_price',
    'booking_fee',
    'deposit_amount',
    'deposit_status',
    'deposit_paid_at',
    'risk_score',
    'risk_tier',
    'total_amount',
    'currency',
    'cancellation_fee',
    'cancelled_at',
    'cancelled_by_user_id',
    'cancellation_reason',
    'confirmed_at',
    'completed_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'deposit_paid_at' => 'datetime',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'party_size' => 'integer',
            'service_price' => 'decimal:2',
            'booking_fee' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'risk_score' => 'decimal:4',
            'total_amount' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
        ];
    }

    /**
     * Generate a short, human readable booking reference.
     */
    public static function generateReferenceCode(): string
    {
        return 'SS-'.Str::upper(Str::random(8));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The employee assigned to perform the service.
     *
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    /**
     * The user who cancelled the booking, which may be staff or the client.
     *
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * @return HasMany<BookingHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(BookingHistory::class);
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * @return HasMany<WaitlistEntry, $this>
     */
    public function freedWaitlistOffers(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class, 'freed_booking_id');
    }

    /**
     * Limit the query to bookings that still occupy capacity.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function occupying(Builder $query): void
    {
        $query->whereIn('status', [
            BookingStatus::Pending->value,
            BookingStatus::Confirmed->value,
        ]);
    }

    /**
     * Limit the query to bookings for the given day.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function forDay(Builder $query, CarbonInterface $day): void
    {
        $query->whereDate('start_at', $day);
    }

    /**
     * Limit the query to bookings that overlap the given window.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('start_at', '<', $to)->where('end_at', '>', $from);
    }

    /**
     * The cancellation fee owed if the client cancels right now.
     *
     * A flat per-service fee applies unless the cancellation lands outside the
     * free cancellation window. Swap this out for a fee strategy later without
     * touching the callers.
     */
    public function cancellationFeeDue(?CarbonInterface $at = null): string
    {
        $at ??= Date::now();
        $service = $this->service;
        $fee = (float) $service->cancellation_fee;

        if ($fee <= 0) {
            return '0.00';
        }

        $freeHours = $service->free_cancellation_hours;

        if ($freeHours !== null && $at->diffInHours($this->start_at, false) >= $freeHours) {
            return '0.00';
        }

        return number_format($fee, 2, '.', '');
    }
}
