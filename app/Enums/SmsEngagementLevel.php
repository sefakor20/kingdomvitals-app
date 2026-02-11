<?php

declare(strict_types=1);

namespace App\Enums;

enum SmsEngagementLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::High => 'green',
            self::Medium => 'blue',
            self::Low => 'yellow',
            self::Inactive => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::High => 'signal',
            self::Medium => 'minus',
            self::Low => 'arrow-trending-down',
            self::Inactive => 'x-circle',
        };
    }

    /**
     * Get the score range for this engagement level.
     *
     * @return array{min: int, max: int}
     */
    public function scoreRange(): array
    {
        return match ($this) {
            self::High => ['min' => 80, 'max' => 100],
            self::Medium => ['min' => 50, 'max' => 79],
            self::Low => ['min' => 20, 'max' => 49],
            self::Inactive => ['min' => 0, 'max' => 19],
        };
    }

    /**
     * Determine the engagement level from a score.
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 80 => self::High,
            $score >= 50 => self::Medium,
            $score >= 20 => self::Low,
            default => self::Inactive,
        };
    }

    /**
     * Check if this level should receive reduced messaging frequency.
     */
    public function shouldReduceFrequency(): bool
    {
        return in_array($this, [self::Low, self::Inactive], true);
    }

    /**
     * Check if this level indicates active engagement.
     */
    public function isEngaged(): bool
    {
        return in_array($this, [self::High, self::Medium], true);
    }

    /**
     * Get recommended messages per month for this engagement level.
     */
    public function recommendedMonthlyMessages(): int
    {
        return match ($this) {
            self::High => 8,
            self::Medium => 4,
            self::Low => 2,
            self::Inactive => 1,
        };
    }
}
