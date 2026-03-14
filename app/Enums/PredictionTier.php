<?php

declare(strict_types=1);

namespace App\Enums;

enum PredictionTier: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'High Probability',
            self::Medium => 'Medium Probability',
            self::Low => 'Low Probability',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::High => 'green',
            self::Medium => 'yellow',
            self::Low => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::High => 'check-circle',
            self::Medium => 'minus-circle',
            self::Low => 'x-circle',
        };
    }

    /**
     * Get tier based on probability score.
     */
    public static function fromProbability(float $probability): self
    {
        return match (true) {
            $probability >= 70 => self::High,
            $probability >= 40 => self::Medium,
            default => self::Low,
        };
    }

    /**
     * Check if this tier warrants sending an invitation.
     */
    public function shouldSendInvitation(): bool
    {
        return $this === self::High || $this === self::Medium;
    }
}
