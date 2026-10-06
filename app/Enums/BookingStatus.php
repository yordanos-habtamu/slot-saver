<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /**
     * Determine whether the booking still occupies capacity.
     */
    public function holdsCapacity(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed => true,
            self::Cancelled, self::Completed, self::NoShow => false,
        };
    }

    /**
     * Determine whether the booking is finished and can no longer change.
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Cancelled, self::Completed, self::NoShow => true,
            self::Pending, self::Confirmed => false,
        };
    }
}
