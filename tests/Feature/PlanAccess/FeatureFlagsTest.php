<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\SupportLevel;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Policies\PrayerRequestPolicy;
use App\Services\PlanAccessService;
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
// PRAYER CHAIN SMS FEATURE TESTS
// ============================================

it('allows prayer chain SMS when feature enabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['prayer_chain_sms', 'bulk_sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $policy = new PrayerRequestPolicy;

    expect($policy->sendPrayerChain($this->admin, $prayerRequest))->toBeTrue();
});

it('blocks prayer chain SMS when feature disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_sms'], // prayer_chain_sms not included
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $policy = new PrayerRequestPolicy;

    expect($policy->sendPrayerChain($this->admin, $prayerRequest))->toBeFalse();
});

it('allows prayer chain SMS when features is null (unlimited)', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'features' => null, // All features enabled
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $policy = new PrayerRequestPolicy;

    expect($policy->sendPrayerChain($this->admin, $prayerRequest))->toBeTrue();
});

it('blocks prayer chain SMS for unauthorized users even when feature enabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['prayer_chain_sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    // Create volunteer user (not authorized for prayer chain)
    $volunteer = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $volunteer->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $prayerRequest = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $policy = new PrayerRequestPolicy;

    expect($policy->sendPrayerChain($volunteer, $prayerRequest))->toBeFalse();
});

// ============================================
// BULK SMS SCHEDULING FEATURE TESTS
// ============================================

it('allows SMS scheduling when feature enabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['bulk_sms_scheduling', 'bulk_sms'],
        'sms_credits_monthly' => 1000,
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('bulk_sms_scheduling'))->toBeTrue();
});

it('blocks SMS scheduling when feature disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['bulk_sms'], // bulk_sms_scheduling not included
        'sms_credits_monthly' => 100,
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('bulk_sms_scheduling'))->toBeFalse();
});

it('allows SMS scheduling when features is null (unlimited)', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Unlimited Plan',
        'slug' => 'unlimited',
        'price_monthly' => 99.00,
        'price_annual' => 999.00,
        'features' => null, // All features enabled
        'sms_credits_monthly' => null,
        'support_level' => SupportLevel::Priority,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('bulk_sms_scheduling'))->toBeTrue();
});

it('checks bulk_sms_scheduling via PlanAccessService hasFeature', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['bulk_sms_scheduling'],
        'sms_credits_monthly' => 1000,
        'enabled_modules' => ['sms'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // This is what the SmsCompose canScheduleSms computed property checks
    expect($service->hasFeature('bulk_sms_scheduling'))->toBeTrue();
});

it('checks bulk_sms_scheduling returns false via PlanAccessService when disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_sms'], // bulk_sms_scheduling not included
        'sms_credits_monthly' => 100,
        'enabled_modules' => ['sms'],
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // This is what the SmsCompose canScheduleSms computed property checks
    expect($service->hasFeature('bulk_sms_scheduling'))->toBeFalse();
});

// ============================================
// MEMBER IMPORT FEATURE TESTS (PLACEHOLDER)
// ============================================

it('allows member import when feature enabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['member_import', 'bulk_operations'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('member_import'))->toBeTrue();
});

it('blocks member import when feature disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_operations'], // member_import not included
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('member_import'))->toBeFalse();
});

it('checks member_import via PlanAccessService hasFeature', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan',
        'slug' => 'pro',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'features' => ['member_import'],
        'enabled_modules' => ['members'],
        'support_level' => SupportLevel::Email,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // This is what the MemberIndex canImportMembers computed property checks
    expect($service->hasFeature('member_import'))->toBeTrue();
});

it('checks member_import returns false via PlanAccessService when disabled', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'features' => ['basic_members'], // member_import not included
        'enabled_modules' => ['members'],
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    // This is what the MemberIndex canImportMembers computed property checks
    expect($service->hasFeature('member_import'))->toBeFalse();
});

// ============================================
// EDGE CASES
// ============================================

it('allows all features when no plan assigned (defaults to unlimited)', function (): void {
    // No subscription plan assigned
    $this->tenant->update(['subscription_id' => null]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('prayer_chain_sms'))->toBeTrue();
    expect($service->hasFeature('bulk_sms_scheduling'))->toBeTrue();
    expect($service->hasFeature('member_import'))->toBeTrue();
});

it('blocks all restricted features when empty features array', function (): void {
    $plan = SubscriptionPlan::create([
        'name' => 'Minimal Plan',
        'slug' => 'minimal',
        'price_monthly' => 5.00,
        'price_annual' => 50.00,
        'features' => [], // No features enabled
        'support_level' => SupportLevel::Community,
    ]);
    $this->tenant->update(['subscription_id' => $plan->id]);

    $service = new PlanAccessService($this->tenant);

    expect($service->hasFeature('prayer_chain_sms'))->toBeFalse();
    expect($service->hasFeature('bulk_sms_scheduling'))->toBeFalse();
    expect($service->hasFeature('member_import'))->toBeFalse();
});
