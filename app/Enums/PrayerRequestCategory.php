<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerRequestCategory: string
{
    case Personal = 'personal';
    case Family = 'family';
    case Health = 'health';
    case Finances = 'finances';
    case Work = 'work';
    case Spiritual = 'spiritual';
    case Relationships = 'relationships';
    case Grief = 'grief';
    case Guidance = 'guidance';
    case Thanksgiving = 'thanksgiving';
    case Other = 'other';
}
