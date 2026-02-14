<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Currency;

/**
 * Service for formatting currency amounts.
 *
 * Provides consistent currency formatting throughout the application.
 */
class CurrencyFormatter
{
    /**
     * Format an amount with the currency symbol.
     *
     * @param  float|int|string  $amount  The amount to format
     * @param  Currency|string  $currency  The currency to use
     * @return string Formatted amount with symbol (e.g., "₵100.00" or "$100.00")
     */
    public function format(float|int|string $amount, Currency|string $currency): string
    {
        $currency = $this->resolveCurrency($currency);
        $amount = $this->normalizeAmount($amount);

        $formatted = number_format($amount, $currency->decimalPlaces(), '.', ',');

        return $currency->symbol().$formatted;
    }

    /**
     * Format an amount with the currency code.
     *
     * @param  float|int|string  $amount  The amount to format
     * @param  Currency|string  $currency  The currency to use
     * @return string Formatted amount with code (e.g., "GHS 100.00" or "USD 100.00")
     */
    public function formatWithCode(float|int|string $amount, Currency|string $currency): string
    {
        $currency = $this->resolveCurrency($currency);
        $amount = $this->normalizeAmount($amount);

        $formatted = number_format($amount, $currency->decimalPlaces(), '.', ',');

        return $currency->code().' '.$formatted;
    }

    /**
     * Format an amount with both symbol and code.
     *
     * @param  float|int|string  $amount  The amount to format
     * @param  Currency|string  $currency  The currency to use
     * @return string Formatted amount (e.g., "₵100.00 GHS")
     */
    public function formatFull(float|int|string $amount, Currency|string $currency): string
    {
        $currency = $this->resolveCurrency($currency);
        $amount = $this->normalizeAmount($amount);

        $formatted = number_format($amount, $currency->decimalPlaces(), '.', ',');

        return $currency->symbol().$formatted.' '.$currency->code();
    }

    /**
     * Convert an amount to subunits (e.g., dollars to cents, cedis to pesewas).
     *
     * @param  float|int  $amount  The amount in main units
     * @param  Currency|string  $currency  The currency to use
     * @return int Amount in subunits
     */
    public function toSubunits(float|int $amount, Currency|string $currency): int
    {
        $currency = $this->resolveCurrency($currency);

        return (int) round($amount * $currency->subunitMultiplier());
    }

    /**
     * Convert subunits to main currency units (e.g., cents to dollars, pesewas to cedis).
     *
     * @param  int  $subunits  The amount in subunits
     * @param  Currency|string  $currency  The currency to use
     * @return float Amount in main units
     */
    public function fromSubunits(int $subunits, Currency|string $currency): float
    {
        $currency = $this->resolveCurrency($currency);

        return $subunits / $currency->subunitMultiplier();
    }

    /**
     * Parse a formatted string back to a float.
     *
     * @param  string  $formatted  The formatted amount string
     * @return float The numeric amount
     */
    public function parse(string $formatted): float
    {
        // Remove currency symbols and codes
        $cleaned = preg_replace('/[₵$]|GHS|USD/i', '', $formatted);

        // Remove thousand separators and trim
        $cleaned = str_replace(',', '', trim($cleaned ?? ''));

        return (float) $cleaned;
    }

    /**
     * Get the currency symbol for display.
     *
     * @param  Currency|string  $currency  The currency
     * @return string The currency symbol
     */
    public function symbol(Currency|string $currency): string
    {
        return $this->resolveCurrency($currency)->symbol();
    }

    /**
     * Resolve a currency from string or enum.
     */
    private function resolveCurrency(Currency|string $currency): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        return Currency::fromString($currency);
    }

    /**
     * Normalize an amount to a float.
     */
    private function normalizeAmount(float|int|string $amount): float
    {
        if (is_string($amount)) {
            // Remove any existing formatting
            $amount = str_replace(',', '', $amount);

            return (float) $amount;
        }

        return (float) $amount;
    }
}
