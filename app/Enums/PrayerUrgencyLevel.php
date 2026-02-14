<?php

declare(strict_types=1);

namespace App\Enums;

enum PrayerUrgencyLevel: string
{
    case Normal = 'normal';
    case Elevated = 'elevated';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Elevated => 'Elevated',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'zinc',
            self::Elevated => 'yellow',
            self::High => 'amber',
            self::Critical => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Normal => 'chat-bubble-left',
            self::Elevated => 'exclamation-circle',
            self::High => 'exclamation-triangle',
            self::Critical => 'bell-alert',
        };
    }

    public function shouldNotifyPastor(): bool
    {
        return in_array($this, [self::High, self::Critical], true);
    }

    public function shouldEscalate(): bool
    {
        return $this === self::Critical;
    }

    /**
     * Get the priority weight for scoring.
     */
    public function priorityWeight(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::Elevated => 15,
            self::High => 30,
            self::Critical => 40,
        };
    }
}
