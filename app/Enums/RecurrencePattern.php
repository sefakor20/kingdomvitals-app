<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrencePattern: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Weekly',
            self::Biweekly => 'Bi-weekly',
            self::Monthly => 'Monthly',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Weekly => 'Repeats every week on the same day',
            self::Biweekly => 'Repeats every two weeks on the same day',
            self::Monthly => 'Repeats every month on the same date',
        };
    }

    /**
     * Get the number of days between occurrences.
     * For monthly, returns approximate days (used for estimation).
     */
    public function intervalDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Biweekly => 14,
            self::Monthly => 30,
        };
    }
}
