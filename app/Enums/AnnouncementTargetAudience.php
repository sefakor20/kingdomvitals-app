<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementTargetAudience: string
{
    case All = 'all';
    case Active = 'active';
    case Trial = 'trial';
    case Suspended = 'suspended';
    case Specific = 'specific';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All Tenants',
            self::Active => 'Active Tenants Only',
            self::Trial => 'Trial Tenants Only',
            self::Suspended => 'Suspended Tenants Only',
            self::Specific => 'Specific Tenants',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::All => 'Send to all tenants regardless of status',
            self::Active => 'Send only to tenants with active subscriptions',
            self::Trial => 'Send only to tenants currently on trial',
            self::Suspended => 'Send only to suspended tenants',
            self::Specific => 'Select specific tenants to receive this announcement',
        };
    }
}
