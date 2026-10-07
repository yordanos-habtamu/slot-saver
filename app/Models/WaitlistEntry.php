<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $business_id
 * @property int $client_user_id
 * @property int $service_id
 * @property int|null $preferred_employee_id
 * @property CarbonInterface $preferred_date
 * @property string|null $preferred_time_from
 * @property string|null $preferred_time_to
 * @property string $status
 * @property string|null $claim_token
 * @property CarbonInterface|null $offered_at
 * @property CarbonInterface|null $offer_expires_at
 * @property int|null $freed_booking_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Business $business
 * @property-read User $client
 * @property-read Service $service
 * @property-read User|null $preferredEmployee
 * @property-read Booking|null $freedBooking
 */
#[Fillable([
    'business_id',
    'client_user_id',
    'service_id',
    'preferred_employee_id',
    'preferred_date',
    'preferred_time_from',
    'preferred_time_to',
    'status',
    'claim_token',
    'offered_at',
    'offer_expires_at',
    'freed_booking_id',
])]
class WaitlistEntry extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'offered_at' => 'datetime',
            'offer_expires_at' => 'datetime',
        ];
    }

    /**
     * Generate a cryptographically secure token for one-tap claiming.
     */
    public static function generateClaimToken(): string
    {
        return Str::random(40);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function preferredEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preferred_employee_id');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function freedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'freed_booking_id');
    }

    /**
     * Scope query to active waiting candidates.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function waiting(Builder $query): void
    {
        $query->where('status', 'waiting');
    }

    /**
     * Scope query to offers that have expired without being claimed.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function expiredOffers(Builder $query): void
    {
        $query->where('status', 'offered')
            ->whereNotNull('offer_expires_at')
            ->where('offer_expires_at', '<=', now());
    }

    /**
     * Whether this offer can currently be claimed.
     */
    public function isClaimable(): bool
    {
        return $this->status === 'offered'
            && $this->offer_expires_at !== null
            && $this->offer_expires_at->isFuture();
    }
}
