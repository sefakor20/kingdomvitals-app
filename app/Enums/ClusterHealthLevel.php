<?php

declare(strict_types=1);

namespace App\Enums;

enum ClusterHealthLevel: string
{
    case Thriving = 'thriving';
    case Healthy = 'healthy';
    case Stable = 'stable';
    case Struggling = 'struggling';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Thriving => 'Thriving',
            self::Healthy => 'Healthy',
            self::Stable => 'Stable',
            self::Struggling => 'Struggling',
            self::Critical => 'Critical',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Thriving => 'Excellent attendance, growth, and engagement',
            self::Healthy => 'Good overall metrics with room for improvement',
            self::Stable => 'Maintaining consistent but modest engagement',
            self::Struggling => 'Declining metrics requiring attention',
            self::Critical => 'Significant issues requiring immediate intervention',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Thriving => 'green',
            self::Healthy => 'blue',
            self::Stable => 'cyan',
            self::Struggling => 'amber',
            self::Critical => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Thriving => 'star',
            self::Healthy => 'check-circle',
            self::Stable => 'minus-circle',
            self::Struggling => 'exclamation-circle',
            self::Critical => 'exclamation-triangle',
        };
    }

    /**
     * Get the score range for this health level.
     *
     * @return array{min: int, max: int}
     */
    public function scoreRange(): array
    {
        return match ($this) {
            self::Thriving => ['min' => 80, 'max' => 100],
            self::Healthy => ['min' => 60, 'max' => 79],
            self::Stable => ['min' => 40, 'max' => 59],
            self::Struggling => ['min' => 20, 'max' => 39],
            self::Critical => ['min' => 0, 'max' => 19],
        };
    }

    /**
     * Determine the health level from a score.
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 80 => self::Thriving,
            $score >= 60 => self::Healthy,
            $score >= 40 => self::Stable,
            $score >= 20 => self::Struggling,
            default => self::Critical,
        };
    }

    /**
     * Check if this level needs attention.
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Struggling, self::Critical], true);
    }

    /**
     * Check if this level is performing well.
     */
    public function isPerformingWell(): bool
    {
        return in_array($this, [self::Thriving, self::Healthy], true);
    }

    /**
     * Get priority for intervention (higher = more urgent).
     */
    public function interventionPriority(): int
    {
        return match ($this) {
            self::Critical => 100,
            self::Struggling => 75,
            self::Stable => 50,
            self::Healthy => 25,
            self::Thriving => 0,
        };
    }

    /**
     * Get recommended check-in frequency in days.
     */
    public function checkInFrequencyDays(): int
    {
        return match ($this) {
            self::Critical => 7,
            self::Struggling => 14,
            self::Stable => 30,
            self::Healthy => 45,
            self::Thriving => 60,
        };
    }

    /**
     * Get all levels that require leadership attention.
     *
     * @return array<self>
     */
    public static function attentionLevels(): array
    {
        return [self::Struggling, self::Critical];
    }
}
