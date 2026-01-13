<?php

declare(strict_types=1);

use App\Enums\SupportLevel;
use App\Enums\TenantStatus;
use App\Livewire\SuperAdmin\Revenue\RevenueDashboard;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('can view revenue dashboard page', function (): void {
    $admin = SuperAdmin::factory()->create();

    $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.revenue'))
        ->assertOk()
        ->assertSee('Revenue Dashboard');
});

it('shows key revenue metrics', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('MRR')
        ->assertSee('ARR')
        ->assertSee('Active Subscribers')
        ->assertSee('In Trial')
        ->assertSee('Conversion Rate')
        ->assertSee('Churned');
});

it('calculates MRR correctly based on active tenants and their plans', function (): void {
    $admin = SuperAdmin::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Pro Plan MRR',
        'slug' => 'pro-plan-mrr',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
    ]);

    // Create 3 active tenants on this plan
    for ($i = 1; $i <= 3; $i++) {
        DB::table('tenants')->insert([
            'id' => "tenant-mrr-test-{$i}",
            'name' => "MRR Test Tenant {$i}",
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Expected MRR: 3 × 100 = 300 GHS
    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('300.00');
});

it('shows plan distribution with tenant counts', function (): void {
    $admin = SuperAdmin::factory()->create();

    $basicPlan = SubscriptionPlan::create([
        'name' => 'Basic Distribution',
        'slug' => 'basic-distribution',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    DB::table('tenants')->insert([
        'id' => 'tenant-dist-test',
        'name' => 'Distribution Test Tenant',
        'status' => TenantStatus::Active->value,
        'subscription_id' => $basicPlan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('Basic Distribution')
        ->assertSee('1 tenants');
});

it('shows monthly trends section', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee("This Month's Trends")
        ->assertSee('New Subscribers')
        ->assertSee('Churned')
        ->assertSee('Net Growth');
});

it('counts trial tenants correctly', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Create trial tenants
    for ($i = 1; $i <= 2; $i++) {
        DB::table('tenants')->insert([
            'id' => "trial-tenant-count-{$i}",
            'name' => "Trial Tenant {$i}",
            'status' => TenantStatus::Trial->value,
            'trial_ends_at' => now()->addDays(14),
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('In Trial');
});

it('calculates conversion rate correctly', function (): void {
    $admin = SuperAdmin::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Conversion Test Plan',
        'slug' => 'conversion-test-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    // 2 active tenants
    for ($i = 1; $i <= 2; $i++) {
        DB::table('tenants')->insert([
            'id' => "conv-active-{$i}",
            'name' => "Active {$i}",
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // 1 trial tenant
    DB::table('tenants')->insert([
        'id' => 'conv-trial-1',
        'name' => 'Trial 1',
        'status' => TenantStatus::Trial->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1 suspended tenant
    DB::table('tenants')->insert([
        'id' => 'conv-suspended-1',
        'name' => 'Suspended 1',
        'status' => TenantStatus::Suspended->value,
        'suspended_at' => now(),
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Conversion rate: 2 / (2 + 1 + 1) = 50%
    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('50%');
});

it('shows revenue breakdown table', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('Revenue Breakdown by Plan')
        ->assertSee('Monthly Price')
        ->assertSee('Active Subscribers')
        ->assertSee('Monthly Revenue');
});

it('uses the correct layout', function (): void {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class);

    expect($component->instance()->render()->name())->toBe('livewire.super-admin.revenue.revenue-dashboard');
});

it('shows empty state when no active plans exist', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Ensure no active plans exist
    SubscriptionPlan::query()->update(['is_active' => false]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('No active plans found');
});

it('excludes inactive plans from distribution', function (): void {
    $admin = SuperAdmin::factory()->create();

    SubscriptionPlan::create([
        'name' => 'Inactive Plan Test',
        'slug' => 'inactive-plan-test',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_active' => false,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertDontSee('Inactive Plan Test');
});

it('calculates ARR as MRR times 12', function (): void {
    $admin = SuperAdmin::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'ARR Test Plan',
        'slug' => 'arr-test-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    DB::table('tenants')->insert([
        'id' => 'arr-test-tenant',
        'name' => 'ARR Test Tenant',
        'status' => TenantStatus::Active->value,
        'subscription_id' => $plan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // MRR = 100, ARR = 1200
    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('100.00') // MRR
        ->assertSee('1,200.00'); // ARR
});

it('counts churned tenants as suspended plus inactive', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Create suspended tenants
    DB::table('tenants')->insert([
        'id' => 'churn-suspended-1',
        'name' => 'Suspended Tenant',
        'status' => TenantStatus::Suspended->value,
        'suspended_at' => now(),
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create inactive tenant
    DB::table('tenants')->insert([
        'id' => 'churn-inactive-1',
        'name' => 'Inactive Tenant',
        'status' => TenantStatus::Inactive->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('Churned');
});

it('shows default badge for default plans in table', function (): void {
    $admin = SuperAdmin::factory()->create();

    SubscriptionPlan::create([
        'name' => 'Default Plan Badge',
        'slug' => 'default-plan-badge',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_default' => true,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->assertSee('Default Plan Badge')
        ->assertSee('Default');
});
