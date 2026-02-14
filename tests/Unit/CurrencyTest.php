<?php

declare(strict_types=1);

use App\Enums\Currency;

describe('Currency Enum', function () {
    it('has GHS and USD cases', function () {
        expect(Currency::cases())->toHaveCount(2);
        expect(Currency::GHS)->toBeInstanceOf(Currency::class);
        expect(Currency::USD)->toBeInstanceOf(Currency::class);
    });

    it('returns correct symbols', function () {
        expect(Currency::GHS->symbol())->toBe('₵');
        expect(Currency::USD->symbol())->toBe('$');
    });

    it('returns correct names', function () {
        expect(Currency::GHS->name())->toBe('Ghanaian Cedi');
        expect(Currency::USD->name())->toBe('US Dollar');
    });

    it('returns correct codes', function () {
        expect(Currency::GHS->code())->toBe('GHS');
        expect(Currency::USD->code())->toBe('USD');
    });

    it('returns correct decimal places', function () {
        expect(Currency::GHS->decimalPlaces())->toBe(2);
        expect(Currency::USD->decimalPlaces())->toBe(2);
    });

    it('returns correct subunit multiplier', function () {
        expect(Currency::GHS->subunitMultiplier())->toBe(100);
        expect(Currency::USD->subunitMultiplier())->toBe(100);
    });

    it('returns correct subunit names', function () {
        expect(Currency::GHS->subunitName())->toBe('pesewas');
        expect(Currency::USD->subunitName())->toBe('cents');
    });

    it('provides options for select inputs', function () {
        $options = Currency::options();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(2);
        expect($options['GHS'])->toContain('₵');
        expect($options['GHS'])->toContain('Ghanaian Cedi');
        expect($options['USD'])->toContain('$');
        expect($options['USD'])->toContain('US Dollar');
    });

    it('provides currency codes as key-value pairs', function () {
        $codes = Currency::codes();

        expect($codes)->toBe([
            'GHS' => 'GHS',
            'USD' => 'USD',
        ]);
    });

    it('creates from string value', function () {
        expect(Currency::fromString('GHS'))->toBe(Currency::GHS);
        expect(Currency::fromString('USD'))->toBe(Currency::USD);
        expect(Currency::fromString('ghs'))->toBe(Currency::GHS);
        expect(Currency::fromString('usd'))->toBe(Currency::USD);
    });

    it('returns default for invalid string', function () {
        expect(Currency::fromString('EUR'))->toBe(Currency::GHS);
        expect(Currency::fromString('invalid'))->toBe(Currency::GHS);
        expect(Currency::fromString(null))->toBe(Currency::GHS);
    });

    it('allows custom default for invalid string', function () {
        expect(Currency::fromString('EUR', Currency::USD))->toBe(Currency::USD);
        expect(Currency::fromString(null, Currency::USD))->toBe(Currency::USD);
    });

    it('has correct string values', function () {
        expect(Currency::GHS->value)->toBe('GHS');
        expect(Currency::USD->value)->toBe('USD');
    });
});
