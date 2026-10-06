<?php

namespace App\Models;

use App\Enums\HistoryAction;
use Carbon\CarbonInterface;
use Database\Factories\BookingHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $client_user_id
 * @property int|null $booking_id
 * @property int|null $business_id
 * @property int|null $service_id
 * @property HistoryAction $action
 * @property string $business_name
 * @property string $service_name
 * @property string|null $location_name
 * @property CarbonInterface|null $scheduled_start_at
 * @property CarbonInterface|null $scheduled_end_at
 * @property string $amount
 * @property string $currency
 * @property string|null $note
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $client
 * @property-read Booking|null $booking
 * @property-read Business|null $business
 * @property-read Service|null $service
 */
#[Fillable([
    'client_user_id',
    'booking_id',
    'business_id',
    'service_id',
    'action',
    'business_name',
    'service_name',
    'location_name',
    'scheduled_start_at',
    'scheduled_end_at',
    'amount',
    'currency',
    'note',
])]
class BookingHistory extends Model
{
    /** @use HasFactory<BookingHistoryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'booking_history';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => HistoryAction::class,
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
