<?php

declare(strict_types=1);

namespace App\Enums;

enum HouseholdEngagementLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Disengaged = 'disengaged';
    case PartiallyEngaged = 'partially_engaged';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Highly Engaged',
            self::Medium => 'Moderately Engaged',
            self::Low => 'Low Engagement',
            self::Disengaged => 'Disengaged',
            self::PartiallyEngaged => 'Partially Engaged',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::High => 'All household members actively participating',
            self::Medium => 'Regular participation from most members',
            self::Low => 'Occasional participation from household',
            self::Disengaged => 'No recent activity from household members',
            self::PartiallyEngaged => 'Some members active, others inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::High => 'green',
            self::Medium => 'blue',
            self::Low => 'yellow',
            self::Disengaged => 'zinc',
            self::PartiallyEngaged => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::High => 'home',
            self::Medium => 'home-modern',
            self::Low => 'building-office',
            self::Disengaged => 'building-office-2',
            self::PartiallyEngaged => 'users',
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
            self::High => ['min' => 70, 'max' => 100],
            self::Medium => ['min' => 40, 'max' => 69],
            self::Low => ['min' => 20, 'max' => 39],
            self::Disengaged => ['min' => 0, 'max' => 19],
            self::PartiallyEngaged => ['min' => 30, 'max' => 60],
        };
    }

    /**
     * Determine the engagement level from a score and variance.
     */
    public static function fromScoreAndVariance(float $score, float $variance, float $varianceThreshold = 30): self
    {
        // If variance is high, household is partially engaged
        if ($variance >= $varianceThreshold && $score >= 30 && $score <= 60) {
            return self::PartiallyEngaged;
        }

        return match (true) {
            $score >= 70 => self::High,
            $score >= 40 => self::Medium,
            $score >= 20 => self::Low,
            default => self::Disengaged,
        };
    }

    /**
     * Determine the engagement level from a score only.
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 70 => self::High,
            $score >= 40 => self::Medium,
            $score >= 20 => self::Low,
            default => self::Disengaged,
        };
    }

    /**
     * Check if this level needs outreach.
     */
    public function needsOutreach(): bool
    {
        return in_array($this, [self::Low, self::Disengaged, self::PartiallyEngaged], true);
    }

    /**
     * Check if this level is actively engaged.
     */
    public function isEngaged(): bool
    {
        return in_array($this, [self::High, self::Medium], true);
    }

    /**
     * Check if this represents partial family engagement.
     */
    public function hasEngagementGap(): bool
    {
        return $this === self::PartiallyEngaged;
    }
}
