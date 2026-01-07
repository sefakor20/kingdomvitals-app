<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Sent => 'green',
            self::Failed => 'red',
        };
    }
}
