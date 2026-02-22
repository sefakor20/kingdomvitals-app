<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Conference = 'conference';
    case Workshop = 'workshop';
    case Seminar = 'seminar';
    case Retreat = 'retreat';
    case Training = 'training';
    case Social = 'social';
    case Fundraiser = 'fundraiser';
    case Special = 'special';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Conference => 'Conference',
            self::Workshop => 'Workshop',
            self::Seminar => 'Seminar',
            self::Retreat => 'Retreat',
            self::Training => 'Training',
            self::Social => 'Social',
            self::Fundraiser => 'Fundraiser',
            self::Special => 'Special Event',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Conference => 'megaphone',
            self::Workshop => 'wrench-screwdriver',
            self::Seminar => 'academic-cap',
            self::Retreat => 'sun',
            self::Training => 'clipboard-document-list',
            self::Social => 'users',
            self::Fundraiser => 'currency-dollar',
            self::Special => 'star',
            self::Other => 'calendar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Conference => 'blue',
            self::Workshop => 'amber',
            self::Seminar => 'purple',
            self::Retreat => 'emerald',
            self::Training => 'cyan',
            self::Social => 'pink',
            self::Fundraiser => 'green',
            self::Special => 'yellow',
            self::Other => 'zinc',
        };
    }
}
