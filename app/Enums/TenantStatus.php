<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Inactive => 'Inactive',
            self::Deleted => 'Deleted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'yellow',
            self::Active => 'green',
            self::Suspended => 'red',
            self::Inactive => 'zinc',
            self::Deleted => 'red',
        };
    }

    public function isAccessible(): bool
    {
        return $this === self::Trial || $this === self::Active;
    }

    public function canBeReactivated(): bool
    {
        return $this === self::Suspended || $this === self::Inactive;
    }
}
