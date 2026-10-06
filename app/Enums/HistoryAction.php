<?php

namespace App\Enums;

enum HistoryAction: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
}
