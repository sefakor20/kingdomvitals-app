<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear any cached system settings
    SystemSetting::clearCache();
});

describe('SubscriptionPlan Multi-Currency', function () {
    describe('manual pricing strategy', function () {
        beforeEach(function () {
            SystemSetting::set('pricing_strategy', 'manual', 'currency');
        });

        it('returns GHS price for monthly', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceMonthly(Currency::GHS))->toBe(100.0);
            expect($plan->getPriceMonthly('GHS'))->toBe(100.0);
        });

        it('returns USD price for monthly', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceMonthly(Currency::USD))->toBe(10.0);
            expect($plan->getPriceMonthly('USD'))->toBe(10.0);
        });

        it('returns GHS price for annual', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceAnnual(Currency::GHS))->toBe(1000.0);
        });

        it('returns USD price for annual', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceAnnual(Currency::USD))->toBe(100.0);
        });

        it('returns zero for null USD prices', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
                'price_monthly_usd' => null,
                'price_annual_usd' => null,
            ]);

            expect($plan->getPriceMonthly(Currency::USD))->toBe(0.0);
            expect($plan->getPriceAnnual(Currency::USD))->toBe(0.0);
        });

        it('defaults to GHS when no currency specified', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00,
            ]);

            expect($plan->getPriceMonthly())->toBe(100.0);
            expect($plan->getPriceAnnual())->toBe(1000.0);
        });
    });

    describe('exchange rate pricing strategy', function () {
        beforeEach(function () {
            SystemSetting::set('pricing_strategy', 'exchange_rate', 'currency');
            SystemSetting::set('base_currency', 'USD', 'currency');
            SystemSetting::set('exchange_rate_usd_to_ghs', '15.00', 'currency');
        });

        it('converts USD to GHS using exchange rate', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 0,
                'price_annual' => 0,
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceMonthly(Currency::GHS))->toBe(150.0);
            expect($plan->getPriceAnnual(Currency::GHS))->toBe(1500.0);
        });

        it('returns base price for same currency', function () {
            $plan = new SubscriptionPlan([
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00,
            ]);

            expect($plan->getPriceMonthly(Currency::USD))->toBe(10.0);
            expect($plan->getPriceAnnual(Currency::USD))->toBe(100.0);
        });

        it('converts GHS base to USD', function () {
            SystemSetting::set('base_currency', 'GHS', 'currency');

            $plan = new SubscriptionPlan([
                'price_monthly' => 150.00,
                'price_annual' => 1500.00,
            ]);

            expect($plan->getPriceMonthly(Currency::USD))->toBe(10.0);
            expect($plan->getPriceAnnual(Currency::USD))->toBe(100.0);
        });
    });

    describe('formatted prices', function () {
        beforeEach(function () {
            SystemSetting::set('pricing_strategy', 'manual', 'currency');
        });

        it('formats GHS prices with symbol', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 1234.56,
                'price_annual' => 12345.67,
            ]);

            expect($plan->getFormattedPriceMonthly(Currency::GHS))->toBe('₵1,234.56');
            expect($plan->getFormattedPriceAnnual(Currency::GHS))->toBe('₵12,345.67');
        });

        it('formats USD prices with symbol', function () {
            $plan = new SubscriptionPlan([
                'price_monthly_usd' => 99.99,
                'price_annual_usd' => 999.99,
            ]);

            expect($plan->getFormattedPriceMonthly(Currency::USD))->toBe('$99.99');
            expect($plan->getFormattedPriceAnnual(Currency::USD))->toBe('$999.99');
        });
    });

    describe('hasPricing', function () {
        it('returns true when GHS prices are set', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 0,
            ]);

            expect($plan->hasPricing(Currency::GHS))->toBeTrue();
        });

        it('returns true when USD prices are set', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 0,
                'price_monthly_usd' => 10.00,
            ]);

            expect($plan->hasPricing(Currency::USD))->toBeTrue();
        });

        it('returns false when no GHS prices set', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 0,
                'price_annual' => 0,
            ]);

            expect($plan->hasPricing(Currency::GHS))->toBeFalse();
        });

        it('returns false when no USD prices set', function () {
            $plan = new SubscriptionPlan([
                'price_monthly_usd' => 0,
                'price_annual_usd' => 0,
            ]);

            expect($plan->hasPricing(Currency::USD))->toBeFalse();
        });
    });

    describe('annual savings', function () {
        beforeEach(function () {
            SystemSetting::set('pricing_strategy', 'manual', 'currency');
        });

        it('calculates savings for GHS', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 100.00,
                'price_annual' => 1000.00, // 2 months free
            ]);

            // 12 * 100 = 1200, savings = 200, percent = 200/1200 = 16.67%
            expect($plan->getAnnualSavingsPercent(Currency::GHS))->toBe(16.7);
        });

        it('calculates savings for USD', function () {
            $plan = new SubscriptionPlan([
                'price_monthly_usd' => 10.00,
                'price_annual_usd' => 100.00, // 2 months free
            ]);

            expect($plan->getAnnualSavingsPercent(Currency::USD))->toBe(16.7);
        });

        it('returns zero when monthly price is zero', function () {
            $plan = new SubscriptionPlan([
                'price_monthly' => 0,
                'price_annual' => 0,
            ]);

            expect($plan->getAnnualSavingsPercent(Currency::GHS))->toBe(0.0);
        });
    });
});
