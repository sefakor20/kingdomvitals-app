<?php

declare(strict_types=1);

namespace App\Enums;

enum AlertSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'amber',
            self::High => 'orange',
            self::Critical => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Low => 'information-circle',
            self::Medium => 'exclamation-circle',
            self::High => 'exclamation-triangle',
            self::Critical => 'x-circle',
        };
    }

    /**
     * Get the priority for sorting (higher = more urgent).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    /**
     * Check if this severity requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return in_array($this, [self::High, self::Critical], true);
    }

    /**
     * Get all severities as options for forms.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
