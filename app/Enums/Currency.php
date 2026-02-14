<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Supported currencies for the platform.
 *
 * Used for tenant financial records and subscription pricing.
 */
enum Currency: string
{
    case GHS = 'GHS';
    case USD = 'USD';

    /**
     * Get the currency symbol.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::GHS => '₵',
            self::USD => '$',
        };
    }

    /**
     * Get the full currency name.
     */
    public function name(): string
    {
        return match ($this) {
            self::GHS => 'Ghanaian Cedi',
            self::USD => 'US Dollar',
        };
    }

    /**
     * Get the currency code (same as value).
     */
    public function code(): string
    {
        return $this->value;
    }

    /**
     * Get the number of decimal places for this currency.
     */
    public function decimalPlaces(): int
    {
        return match ($this) {
            self::GHS, self::USD => 2,
        };
    }

    /**
     * Get the subunit multiplier (e.g., 100 for cents/pesewas).
     */
    public function subunitMultiplier(): int
    {
        return match ($this) {
            self::GHS, self::USD => 100,
        };
    }

    /**
     * Get the subunit name.
     */
    public function subunitName(): string
    {
        return match ($this) {
            self::GHS => 'pesewas',
            self::USD => 'cents',
        };
    }

    /**
     * Get all currencies as options for select inputs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::GHS->value => self::GHS->symbol().' - '.self::GHS->name(),
            self::USD->value => self::USD->symbol().' - '.self::USD->name(),
        ];
    }

    /**
     * Get all currencies as simple key-value pairs.
     *
     * @return array<string, string>
     */
    public static function codes(): array
    {
        return [
            self::GHS->value => self::GHS->value,
            self::USD->value => self::USD->value,
        ];
    }

    /**
     * Create from string value, with fallback to default.
     */
    public static function fromString(?string $value, self $default = self::GHS): self
    {
        if ($value === null) {
            return $default;
        }

        return self::tryFrom(strtoupper($value)) ?? $default;
    }
}
