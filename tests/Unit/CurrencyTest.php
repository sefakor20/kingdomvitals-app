<?php

declare(strict_types=1);

use App\Enums\Currency;

describe('Currency Enum', function () {
    it('has all supported currency cases', function () {
        expect(Currency::cases())->toHaveCount(5);
        expect(Currency::GHS)->toBeInstanceOf(Currency::class);
        expect(Currency::USD)->toBeInstanceOf(Currency::class);
        expect(Currency::GBP)->toBeInstanceOf(Currency::class);
        expect(Currency::EUR)->toBeInstanceOf(Currency::class);
        expect(Currency::NGN)->toBeInstanceOf(Currency::class);
    });

    it('returns correct symbols', function () {
        expect(Currency::GHS->symbol())->toBe('₵');
        expect(Currency::USD->symbol())->toBe('$');
        expect(Currency::GBP->symbol())->toBe('£');
        expect(Currency::EUR->symbol())->toBe('€');
        expect(Currency::NGN->symbol())->toBe('₦');
    });

    it('returns correct names', function () {
        expect(Currency::GHS->name())->toBe('Ghanaian Cedi');
        expect(Currency::USD->name())->toBe('US Dollar');
        expect(Currency::GBP->name())->toBe('British Pound');
        expect(Currency::EUR->name())->toBe('Euro');
        expect(Currency::NGN->name())->toBe('Nigerian Naira');
    });

    it('returns correct codes', function () {
        expect(Currency::GHS->code())->toBe('GHS');
        expect(Currency::USD->code())->toBe('USD');
        expect(Currency::GBP->code())->toBe('GBP');
        expect(Currency::EUR->code())->toBe('EUR');
        expect(Currency::NGN->code())->toBe('NGN');
    });

    it('returns correct decimal places', function () {
        expect(Currency::GHS->decimalPlaces())->toBe(2);
        expect(Currency::USD->decimalPlaces())->toBe(2);
        expect(Currency::GBP->decimalPlaces())->toBe(2);
        expect(Currency::EUR->decimalPlaces())->toBe(2);
        expect(Currency::NGN->decimalPlaces())->toBe(2);
    });

    it('returns correct subunit multiplier', function () {
        expect(Currency::GHS->subunitMultiplier())->toBe(100);
        expect(Currency::USD->subunitMultiplier())->toBe(100);
        expect(Currency::GBP->subunitMultiplier())->toBe(100);
        expect(Currency::EUR->subunitMultiplier())->toBe(100);
        expect(Currency::NGN->subunitMultiplier())->toBe(100);
    });

    it('returns correct subunit names', function () {
        expect(Currency::GHS->subunitName())->toBe('pesewas');
        expect(Currency::USD->subunitName())->toBe('cents');
        expect(Currency::GBP->subunitName())->toBe('pence');
        expect(Currency::EUR->subunitName())->toBe('cents');
        expect(Currency::NGN->subunitName())->toBe('kobo');
    });

    it('provides options for select inputs', function () {
        $options = Currency::options();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);
        expect($options['GHS'])->toContain('₵');
        expect($options['GHS'])->toContain('Ghanaian Cedi');
        expect($options['USD'])->toContain('$');
        expect($options['USD'])->toContain('US Dollar');
        expect($options['GBP'])->toContain('£');
        expect($options['GBP'])->toContain('British Pound');
        expect($options['EUR'])->toContain('€');
        expect($options['EUR'])->toContain('Euro');
        expect($options['NGN'])->toContain('₦');
        expect($options['NGN'])->toContain('Nigerian Naira');
    });

    it('provides currency codes as key-value pairs', function () {
        $codes = Currency::codes();

        expect($codes)->toBe([
            'GHS' => 'GHS',
            'USD' => 'USD',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
            'NGN' => 'NGN',
        ]);
    });

    it('creates from string value', function () {
        expect(Currency::fromString('GHS'))->toBe(Currency::GHS);
        expect(Currency::fromString('USD'))->toBe(Currency::USD);
        expect(Currency::fromString('GBP'))->toBe(Currency::GBP);
        expect(Currency::fromString('EUR'))->toBe(Currency::EUR);
        expect(Currency::fromString('NGN'))->toBe(Currency::NGN);
        expect(Currency::fromString('ghs'))->toBe(Currency::GHS);
        expect(Currency::fromString('usd'))->toBe(Currency::USD);
        expect(Currency::fromString('gbp'))->toBe(Currency::GBP);
        expect(Currency::fromString('eur'))->toBe(Currency::EUR);
        expect(Currency::fromString('ngn'))->toBe(Currency::NGN);
    });

    it('returns default for invalid string', function () {
        expect(Currency::fromString('XYZ'))->toBe(Currency::GHS);
        expect(Currency::fromString('invalid'))->toBe(Currency::GHS);
        expect(Currency::fromString(null))->toBe(Currency::GHS);
    });

    it('allows custom default for invalid string', function () {
        expect(Currency::fromString('XYZ', Currency::USD))->toBe(Currency::USD);
        expect(Currency::fromString(null, Currency::USD))->toBe(Currency::USD);
    });

    it('has correct string values', function () {
        expect(Currency::GHS->value)->toBe('GHS');
        expect(Currency::USD->value)->toBe('USD');
        expect(Currency::GBP->value)->toBe('GBP');
        expect(Currency::EUR->value)->toBe('EUR');
        expect(Currency::NGN->value)->toBe('NGN');
    });
});
