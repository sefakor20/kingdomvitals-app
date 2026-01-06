<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportLevel: string
{
    case Community = 'community';
    case Email = 'email';
    case Priority = 'priority';

    public function label(): string
    {
        return match ($this) {
            self::Community => 'Community Support',
            self::Email => 'Email Support',
            self::Priority => 'Priority Support',
        };
    }

    public function responseTime(): string
    {
        return match ($this) {
            self::Community => 'Best effort (community forums)',
            self::Email => '24-48 hours',
            self::Priority => '4 hours',
        };
    }
}
