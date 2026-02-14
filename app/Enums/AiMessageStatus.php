<?php

declare(strict_types=1);

namespace App\Enums;

enum AiMessageStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Sent = 'sent';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Approved => 'Approved',
            self::Sent => 'Sent',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'blue',
            self::Sent => 'green',
            self::Rejected => 'red',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Sent => 'success',
            self::Rejected => 'danger',
        };
    }
}
