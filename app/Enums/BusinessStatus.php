<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    /**
     * Determine whether the business may accept new bookings.
     */
    public function acceptsBookings(): bool
    {
        return $this === self::Active;
    }
}
