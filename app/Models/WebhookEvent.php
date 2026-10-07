<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $provider
 * @property string $idempotency_key
 * @property string $event_type
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $processed_at
 * @property string $response_status
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'provider',
    'idempotency_key',
    'event_type',
    'payload',
    'processed_at',
    'response_status',
])]
class WebhookEvent extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Determine if this idempotency key has already been recorded.
     */
    public static function isDuplicate(string $key): bool
    {
        return static::where('idempotency_key', $key)->exists();
    }
}
