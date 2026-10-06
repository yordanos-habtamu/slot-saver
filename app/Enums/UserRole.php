<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Employee = 'employee';
    case Client = 'client';

    /**
     * Determine whether the role can sign in and manage bookings.
     */
    public function isStaff(): bool
    {
        return match ($this) {
            self::Admin, self::Owner, self::Employee => true,
            self::Client => false,
        };
    }
}
