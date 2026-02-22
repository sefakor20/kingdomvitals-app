<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationStatus: string
{
    case Registered = 'registered';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::Attended => 'Attended',
            self::NoShow => 'No Show',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Registered => 'blue',
            self::Attended => 'green',
            self::NoShow => 'amber',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Registered => 'ticket',
            self::Attended => 'check-badge',
            self::NoShow => 'user-minus',
            self::Cancelled => 'x-mark',
        };
    }
}
