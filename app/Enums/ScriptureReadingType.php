<?php

declare(strict_types=1);

namespace App\Enums;

enum ScriptureReadingType: string
{
    case FirstReading = 'first_reading';
    case SecondReading = 'second_reading';
    case GospelReading = 'gospel_reading';
    case PsalmReading = 'psalm_reading';
    case ResponsiveReading = 'responsive_reading';
    case OtherReading = 'other_reading';

    public function label(): string
    {
        return match ($this) {
            self::FirstReading => 'First Reading',
            self::SecondReading => 'Second Reading',
            self::GospelReading => 'Gospel Reading',
            self::PsalmReading => 'Psalm Reading',
            self::ResponsiveReading => 'Responsive Reading',
            self::OtherReading => 'Other Reading',
        };
    }
}
