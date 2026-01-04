<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerRequestStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Cancelled = 'cancelled';
}
