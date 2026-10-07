<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property int $day_of_week
 * @property string $opens_at
 * @property string $closes_at
 * @property bool $is_closed
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Location $location
 */
#[Fillable([
    'location_id',
    'day_of_week',
    'opens_at',
    'closes_at',
    'is_closed',
])]
class LocationOpeningHour extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * Monday (1) through Sunday (7).
     */
    public const int MONDAY = 1;

    public const int SUNDAY = 7;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The window as [H:i => H:i] or null when the day is marked closed.
     *
     * @return array{opens: string, closes: string}|null
     */
    public function window(): ?array
    {
        if ($this->is_closed) {
            return null;
        }

        return [
            'opens' => substr($this->opens_at, 0, 5),
            'closes' => substr($this->closes_at, 0, 5),
        ];
    }

    /**
     * Scope query to the row for the given ISO day of week.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForDay(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('day_of_week', $date->dayOfWeekIso);
    }
}
