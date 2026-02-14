<?php

declare(strict_types=1);

namespace App\Enums;

enum ExperienceLevel: string
{
    case Novice = 'novice';
    case Intermediate = 'intermediate';
    case Experienced = 'experienced';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Novice => 'Novice',
            self::Intermediate => 'Intermediate',
            self::Experienced => 'Experienced',
            self::Expert => 'Expert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Novice => 'zinc',
            self::Intermediate => 'blue',
            self::Experienced => 'green',
            self::Expert => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Novice => 'academic-cap',
            self::Intermediate => 'user',
            self::Experienced => 'user-circle',
            self::Expert => 'star',
        };
    }

    /**
     * Get the priority weight for scoring (higher experience = higher weight).
     */
    public function priorityWeight(): int
    {
        return match ($this) {
            self::Novice => 0,
            self::Intermediate => 5,
            self::Experienced => 10,
            self::Expert => 15,
        };
    }

    /**
     * Get the numeric level for comparison (1-4).
     */
    public function level(): int
    {
        return match ($this) {
            self::Novice => 1,
            self::Intermediate => 2,
            self::Experienced => 3,
            self::Expert => 4,
        };
    }

    /**
     * Check if this experience level meets or exceeds a requirement.
     */
    public function meetsRequirement(self $required): bool
    {
        return $this->level() >= $required->level();
    }
}
