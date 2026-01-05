<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerRequestPrivacy: string
{
    case Public = 'public';
    case Private = 'private';
    case LeadersOnly = 'leaders_only';
}
