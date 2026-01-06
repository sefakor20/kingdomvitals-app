<?php

declare(strict_types=1);

namespace App\Enums;

enum SuperAdminRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Administrator',
            self::Support => 'Support Staff',
        };
    }

    public function hasFullAccess(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManageTenants(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManageSuperAdmins(): bool
    {
        return $this === self::Owner;
    }

    public function canImpersonateTenants(): bool
    {
        return $this === self::Owner || $this === self::Admin || $this === self::Support;
    }

    public function canAccessBilling(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
