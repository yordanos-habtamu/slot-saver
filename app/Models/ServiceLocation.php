<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ServiceLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_id
 * @property int $location_id
 * @property string|null $price_override
 * @property int|null $duration_minutes_override
 * @property int|null $max_per_slot_override
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Service $service
 * @property-read Location $location
 */
#[Fillable([
    'service_id',
    'location_id',
    'price_override',
    'duration_minutes_override',
    'max_per_slot_override',
    'is_active',
])]
class ServiceLocation extends Model
{
    /** @use HasFactory<ServiceLocationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_location';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'duration_minutes_override' => 'integer',
            'max_per_slot_override' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
