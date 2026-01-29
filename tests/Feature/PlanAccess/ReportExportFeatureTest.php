<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Policies\ReportPolicy;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create admin user with branch admin access
    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// REPORT EXPORT FEATURE TESTS
// ============================================

it('allows export when reports_export feature enabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['reports_export', 'bulk_sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $policy = new ReportPolicy;

    expect($policy->exportReports($this->admin, $this->branch))->toBeTrue();
});

it('blocks export when reports_export feature disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_reports'], // reports_export not included
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $policy = new ReportPolicy;

    expect($policy->exportReports($this->admin, $this->branch))->toBeFalse();
});

it('allows export when features is null (unlimited)', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'features' => null, // All features enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $policy = new ReportPolicy;

    expect($policy->exportReports($this->admin, $this->branch))->toBeTrue();
});

it('blocks export when user cannot view reports', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['reports_export'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create a user without proper role access
    $regularUser = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $regularUser->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer, // Volunteer cannot view reports
    ]);

    $policy = new ReportPolicy;

    // Even with feature enabled, user without viewReports permission cannot export
    expect($policy->exportReports($regularUser, $this->branch))->toBeFalse();
});

it('allows export when no plan assigned (defaults to unlimited)', function (): void {
    // No subscription plan assigned
    $this->tenant->update(['subscription_id' => null]);

    $policy = new ReportPolicy;

    expect($policy->exportReports($this->admin, $this->branch))->toBeTrue();
});
