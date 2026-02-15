<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Tenant Currency', function (): void {
    describe('currency property', function (): void {
        it('defaults to GHS when not set', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
            ]);

            expect($tenant->getCurrency())->toBe(Currency::GHS);
            expect($tenant->getCurrencyCode())->toBe('GHS');
            expect($tenant->getCurrencySymbol())->toBe('₵');
        });

        it('returns GHS when explicitly set', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::GHS,
            ]);

            expect($tenant->getCurrency())->toBe(Currency::GHS);
            expect($tenant->getCurrencyCode())->toBe('GHS');
            expect($tenant->getCurrencySymbol())->toBe('₵');
        });

        it('returns USD when set', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::USD,
            ]);

            expect($tenant->getCurrency())->toBe(Currency::USD);
            expect($tenant->getCurrencyCode())->toBe('USD');
            expect($tenant->getCurrencySymbol())->toBe('$');
        });

        it('casts currency to enum', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => 'USD',
            ]);

            expect($tenant->currency)->toBeInstanceOf(Currency::class);
            expect($tenant->currency)->toBe(Currency::USD);
        });
    });

    describe('setCurrency method', function (): void {
        it('sets currency with enum', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::GHS,
            ]);

            $tenant->setCurrency(Currency::USD);
            $tenant->refresh();

            expect($tenant->getCurrency())->toBe(Currency::USD);
        });

        it('sets currency with string', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::GHS,
            ]);

            $tenant->setCurrency('USD');
            $tenant->refresh();

            expect($tenant->getCurrency())->toBe(Currency::USD);
        });

        it('handles invalid string by defaulting to GHS', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::USD,
            ]);

            $tenant->setCurrency('INVALID');
            $tenant->refresh();

            expect($tenant->getCurrency())->toBe(Currency::GHS);
        });
    });

    describe('helper methods', function (): void {
        it('getCurrencyCode returns code string', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::USD,
            ]);

            expect($tenant->getCurrencyCode())->toBe('USD');
            expect($tenant->getCurrencyCode())->toBeString();
        });

        it('getCurrencySymbol returns symbol', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::USD,
            ]);

            expect($tenant->getCurrencySymbol())->toBe('$');
        });

        it('getCurrency returns enum', function (): void {
            $tenant = Tenant::create([
                'name' => 'Test Church',
                'currency' => Currency::GHS,
            ]);

            expect($tenant->getCurrency())->toBeInstanceOf(Currency::class);
        });
    });
});
