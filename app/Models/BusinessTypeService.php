<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\BusinessTypeServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_type_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property int $duration_minutes
 * @property string $booking_fee
 * @property string $cancellation_fee
 * @property int|null $free_cancellation_hours
 * @property int|null $max_per_client_per_day
 * @property bool $is_recommended
 * @property int $sort_order
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read BusinessType $businessType
 */
#[Fillable([
    'business_type_id',
    'name',
    'description',
    'price',
    'duration_minutes',
    'booking_fee',
    'cancellation_fee',
    'free_cancellation_hours',
    'max_per_client_per_day',
    'is_recommended',
    'sort_order',
])]
class BusinessTypeService extends Model
{
    /** @use HasFactory<BusinessTypeServiceFactory> */
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
            'duration_minutes' => 'integer',
            'free_cancellation_hours' => 'integer',
            'max_per_client_per_day' => 'integer',
            'is_recommended' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The business type this template belongs to.
     *
     * @return BelongsTo<BusinessType, $this>
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }
}
