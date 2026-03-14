<?php

declare(strict_types=1);

namespace App\Enums;

enum RiskLevel: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'High Risk',
            self::Medium => 'Medium Risk',
            self::Low => 'Low Risk',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::High => 'red',
            self::Medium => 'yellow',
            self::Low => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::High => 'exclamation-triangle',
            self::Medium => 'exclamation-circle',
            self::Low => 'check-circle',
        };
    }

    /**
     * Get risk level based on probability (inverse - low probability = high risk).
     */
    public static function fromFulfillmentProbability(float $probability): self
    {
        return match (true) {
            $probability < 40 => self::High,
            $probability < 70 => self::Medium,
            default => self::Low,
        };
    }

    /**
     * Check if this risk level warrants sending a nudge.
     */
    public function shouldSendNudge(): bool
    {
        return $this === self::High || $this === self::Medium;
    }
}
