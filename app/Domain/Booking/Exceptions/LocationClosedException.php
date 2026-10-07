<?php

namespace App\Domain\Booking\Exceptions;

use InvalidArgumentException;

class LocationClosedException extends InvalidArgumentException
{
    public function __construct(string $message = 'This location is closed for the requested date or time.')
    {
        parent::__construct($message);
    }
}
