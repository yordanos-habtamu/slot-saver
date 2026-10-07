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

/**
 * @property int $id
 * @property int $booking_id
 * @property string $channel
 * @property string $type
 * @property CarbonInterface $scheduled_for
 * @property CarbonInterface|null $sent_at
 * @property string|null $provider_message_id
 * @property string $delivery_status
 * @property string|null $interactive_action
 * @property int $retry_count
 * @property string|null $error_details
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Booking $booking
 */
#[Fillable([
    'booking_id',
    'channel',
    'type',
    'scheduled_for',
    'sent_at',
    'provider_message_id',
    'delivery_status',
    'interactive_action',
    'retry_count',
    'error_details',
])]
class Reminder extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope query to reminders due to be dispatched.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function due(Builder $query): void
    {
        $query->where('delivery_status', 'scheduled')
            ->where('scheduled_for', '<=', now());
    }

    /**
     * Scope query to successfully sent/delivered reminders.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function successful(Builder $query): void
    {
        $query->whereIn('delivery_status', ['sent', 'delivered', 'read']);
    }
}
