<?php

declare(strict_types=1);

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\ProrationService;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->prorationService = new ProrationService;
});

// ============================================
// DAILY RATE CALCULATION TESTS
// ============================================

it('calculates daily rate for monthly plan correctly', function (): void {
    $plan = new SubscriptionPlan([
        'price_monthly' => 300,
        'price_annual' => 3000,
    ]);

    // 30-day month
    $dailyRate = $this->prorationService->getDailyRate($plan, BillingCycle::Monthly, 30);

    expect($dailyRate)->toBe(10.0);
});

it('calculates daily rate for annual plan correctly', function (): void {
    $plan = new SubscriptionPlan([
        'price_monthly' => 300,
        'price_annual' => 3650,
    ]);

    // Annual rate is always divided by 365
    $dailyRate = $this->prorationService->getDailyRate($plan, BillingCycle::Annual, 30);

    expect($dailyRate)->toBe(10.0);
});

it('returns zero daily rate for zero days in period', function (): void {
    $plan = new SubscriptionPlan([
        'price_monthly' => 300,
        'price_annual' => 3000,
    ]);

    $dailyRate = $this->prorationService->getDailyRate($plan, BillingCycle::Monthly, 0);

    expect($dailyRate)->toBe(0.0);
});

// ============================================
// CHANGE TYPE DETERMINATION TESTS
// ============================================

it('determines upgrade when new plan is more expensive', function (): void {
    $currentPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);
    $newPlan = new SubscriptionPlan(['price_monthly' => 200, 'price_annual' => 2000]);

    $changeType = $this->prorationService->determineChangeType(
        $currentPlan,
        $newPlan,
        BillingCycle::Monthly,
        BillingCycle::Monthly
    );

    expect($changeType)->toBe('upgrade');
});

it('determines downgrade when new plan is cheaper', function (): void {
    $currentPlan = new SubscriptionPlan(['price_monthly' => 200, 'price_annual' => 2000]);
    $newPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);

    $changeType = $this->prorationService->determineChangeType(
        $currentPlan,
        $newPlan,
        BillingCycle::Monthly,
        BillingCycle::Monthly
    );

    expect($changeType)->toBe('downgrade');
});

it('determines cycle change when billing cycle differs', function (): void {
    $currentPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);
    $newPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);

    $changeType = $this->prorationService->determineChangeType(
        $currentPlan,
        $newPlan,
        BillingCycle::Monthly,
        BillingCycle::Annual
    );

    expect($changeType)->toBe('cycle_change');
});

it('determines lateral when same price', function (): void {
    $currentPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);
    $newPlan = new SubscriptionPlan(['price_monthly' => 100, 'price_annual' => 1000]);

    $changeType = $this->prorationService->determineChangeType(
        $currentPlan,
        $newPlan,
        BillingCycle::Monthly,
        BillingCycle::Monthly
    );

    expect($changeType)->toBe('lateral');
});

// ============================================
// SHOULD APPLY PRORATION TESTS
// ============================================

it('should not apply proration when no subscription', function (): void {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn(null);

    expect($this->prorationService->shouldApplyProration($tenant))->toBeFalse();
});

it('should not apply proration when no billing period set', function (): void {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('some-plan-id');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')->andReturn(null);
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(null);

    expect($this->prorationService->shouldApplyProration($tenant))->toBeFalse();
});

it('should not apply proration when period has ended', function (): void {
    Carbon::setTestNow('2026-01-15');

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('some-plan-id');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')->andReturn(Carbon::parse('2025-12-01'));
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-10'));

    expect($this->prorationService->shouldApplyProration($tenant))->toBeFalse();

    Carbon::setTestNow();
});

it('should apply proration when in active billing period', function (): void {
    Carbon::setTestNow('2026-01-15');

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('some-plan-id');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')->andReturn(Carbon::parse('2026-01-01'));
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-31'));

    expect($this->prorationService->shouldApplyProration($tenant))->toBeTrue();

    Carbon::setTestNow();
});

// ============================================
// DAYS REMAINING TESTS
// ============================================

it('returns zero days remaining when no period end', function (): void {
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(null);

    expect($this->prorationService->getDaysRemaining($tenant))->toBe(0);
});

it('returns zero days remaining when period has passed', function (): void {
    Carbon::setTestNow('2026-01-15');

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-10'));

    expect($this->prorationService->getDaysRemaining($tenant))->toBe(0);

    Carbon::setTestNow();
});

it('returns correct days remaining for future period end', function (): void {
    Carbon::setTestNow('2026-01-15');

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-25'));

    expect($this->prorationService->getDaysRemaining($tenant))->toBe(10);

    Carbon::setTestNow();
});

// ============================================
// PRORATION CALCULATION TESTS
// ============================================

it('calculates mid-month upgrade proration correctly', function (): void {
    Carbon::setTestNow('2026-01-16'); // Mid-month

    $currentPlan = new SubscriptionPlan([
        'id' => 'plan-1',
        'price_monthly' => 100,
        'price_annual' => 1000,
    ]);

    $newPlan = new SubscriptionPlan([
        'id' => 'plan-2',
        'price_monthly' => 200,
        'price_annual' => 2000,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('plan-1');
    $tenant->shouldReceive('getAttribute')->with('billing_cycle')->andReturn('monthly');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')->andReturn(Carbon::parse('2026-01-01'));
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-31'));
    $tenant->shouldReceive('getRelationValue')->with('subscriptionPlan')->andReturn($currentPlan);

    $result = $this->prorationService->calculatePlanChange($tenant, $newPlan, BillingCycle::Monthly);

    // 15 days remaining (Jan 16 to Jan 31)
    expect($result['days_remaining'])->toBe(15);
    expect($result['change_type'])->toBe('upgrade');
    // Old plan credit: (100/31) * 15 ≈ 48.39 (may round to 50 depending on calculation)
    expect($result['old_plan_credit'])->toBeGreaterThan(40);
    expect($result['old_plan_credit'])->toBeLessThan(60);
    // New plan cost: (200/31) * 15 ≈ 96.77 (may round to 100 depending on calculation)
    expect($result['new_plan_cost'])->toBeGreaterThan(80);
    expect($result['new_plan_cost'])->toBeLessThan(110);
    // Amount due should be positive (upgrade)
    expect($result['amount_due'])->toBeGreaterThan(0);
    expect((float) $result['credit_generated'])->toBe(0.0);

    Carbon::setTestNow();
});

it('calculates mid-month downgrade proration with credit', function (): void {
    Carbon::setTestNow('2026-01-16');

    $currentPlan = new SubscriptionPlan([
        'id' => 'plan-1',
        'price_monthly' => 200,
        'price_annual' => 2000,
    ]);

    $newPlan = new SubscriptionPlan([
        'id' => 'plan-2',
        'price_monthly' => 100,
        'price_annual' => 1000,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('subscription_id')->andReturn('plan-1');
    $tenant->shouldReceive('getAttribute')->with('billing_cycle')->andReturn('monthly');
    $tenant->shouldReceive('getAttribute')->with('current_period_start')->andReturn(Carbon::parse('2026-01-01'));
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-31'));
    $tenant->shouldReceive('getRelationValue')->with('subscriptionPlan')->andReturn($currentPlan);

    $result = $this->prorationService->calculatePlanChange($tenant, $newPlan, BillingCycle::Monthly);

    expect($result['change_type'])->toBe('downgrade');
    // Amount due should be 0 for downgrade
    expect((float) $result['amount_due'])->toBe(0.0);
    // Credit should be generated
    expect((float) $result['credit_generated'])->toBeGreaterThan(0);

    Carbon::setTestNow();
});

it('returns full price when no active billing period', function (): void {
    $newPlan = new SubscriptionPlan([
        'id' => 'plan-2',
        'price_monthly' => 200,
        'price_annual' => 2000,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(null);
    $tenant->shouldReceive('getRelationValue')->with('subscriptionPlan')->andReturn(null);

    $result = $this->prorationService->calculatePlanChange($tenant, $newPlan, BillingCycle::Monthly);

    expect($result['change_type'])->toBe('new_subscription');
    expect((float) $result['amount_due'])->toBe(200.0);
    expect((float) $result['old_plan_credit'])->toBe(0.0);

    Carbon::setTestNow();
});

it('returns full price when period has expired', function (): void {
    Carbon::setTestNow('2026-01-20');

    $currentPlan = new SubscriptionPlan([
        'id' => 'plan-1',
        'price_monthly' => 100,
        'price_annual' => 1000,
    ]);

    $newPlan = new SubscriptionPlan([
        'id' => 'plan-2',
        'price_monthly' => 200,
        'price_annual' => 2000,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->shouldReceive('getAttribute')->with('current_period_end')->andReturn(Carbon::parse('2026-01-15'));
    $tenant->shouldReceive('getRelationValue')->with('subscriptionPlan')->andReturn($currentPlan);

    $result = $this->prorationService->calculatePlanChange($tenant, $newPlan, BillingCycle::Monthly);

    expect($result['change_type'])->toBe('new_subscription');
    expect((float) $result['amount_due'])->toBe(200.0);

    Carbon::setTestNow();
});
