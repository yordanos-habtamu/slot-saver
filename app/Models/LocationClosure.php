<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $location_id
 * @property CarbonInterface $starts_on
 * @property CarbonInterface|null $ends_on
 * @property string|null $reason
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Location $location
 */
#[Fillable([
    'location_id',
    'starts_on',
    'ends_on',
    'reason',
])]
class LocationClosure extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
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
     * Whether the closure covers the given date.
     */
    public function covers(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        if ($this->starts_on->toDateString() > $day) {
            return false;
        }

        return $this->ends_on === null || $this->ends_on->toDateString() >= $day;
    }

    /**
     * A human readable label for the closure window.
     */
    public function label(): string
    {
        if ($this->ends_on === null || $this->ends_on->toDateString() === $this->starts_on->toDateString()) {
            return $this->reason ?: 'Closed on '.$this->starts_on->toDateString();
        }

        return ($this->reason ?: 'Closed').' '.$this->starts_on->toDateString().' → '.$this->ends_on->toDateString();
    }
}
