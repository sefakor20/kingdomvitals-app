<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Ongoing => 'Ongoing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Published => 'blue',
            self::Ongoing => 'green',
            self::Completed => 'emerald',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil-square',
            self::Published => 'globe-alt',
            self::Ongoing => 'play',
            self::Completed => 'check-circle',
            self::Cancelled => 'x-circle',
        };
    }
}
