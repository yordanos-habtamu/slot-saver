<?php

namespace App\Domain\Booking\Exceptions;

use RuntimeException;

class SlotAlreadyBookedException extends RuntimeException
{
    public function __construct(string $message = 'The requested time slot is already booked for this staff member or location.')
    {
        parent::__construct($message);
    }
}
