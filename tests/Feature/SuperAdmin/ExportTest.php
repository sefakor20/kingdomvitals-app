<?php

declare(strict_types=1);

use App\Enums\SupportLevel;
use App\Enums\TenantStatus;
use App\Livewire\SuperAdmin\ActivityLogs;
use App\Livewire\SuperAdmin\Plans\PlanIndex;
use App\Livewire\SuperAdmin\Revenue\RevenueDashboard;
use App\Livewire\SuperAdmin\Tenants\TenantIndex;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ============================================
// Tenant Export Tests
// ============================================

it('can export tenants to CSV', function (): void {
    $admin = SuperAdmin::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'export-tenant-1',
        'name' => 'Export Test Church',
        'status' => TenantStatus::Active->value,
        'contact_email' => 'export@test.com',
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(TenantIndex::class)
        ->call('exportCsv');

    expect($component->effects['download'])->toBeArray();
    expect($component->effects['download']['name'])->toContain('tenants-');
    expect($component->effects['download']['name'])->toContain('.csv');
});

it('logs tenant export activity', function (): void {
    $admin = SuperAdmin::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'log-export-tenant-1',
        'name' => 'Log Export Church',
        'status' => TenantStatus::Active->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(TenantIndex::class)
        ->call('exportCsv');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'export_tenants',
    ]);
});

it('exports filtered tenants when search is active', function (): void {
    $admin = SuperAdmin::factory()->create();

    DB::table('tenants')->insert([
        'id' => 'filtered-export-1',
        'name' => 'Filtered Church',
        'status' => TenantStatus::Active->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('tenants')->insert([
        'id' => 'other-export-1',
        'name' => 'Other Church',
        'status' => TenantStatus::Active->value,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(TenantIndex::class)
        ->set('search', 'Filtered')
        ->call('exportCsv');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'export_tenants',
    ]);
});

// ============================================
// Plan Export Tests
// ============================================

it('can export plans to CSV', function (): void {
    $admin = SuperAdmin::factory()->create();

    SubscriptionPlan::create([
        'name' => 'Export Test Plan',
        'slug' => 'export-test-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
    ]);

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(PlanIndex::class)
        ->call('exportCsv');

    expect($component->effects['download'])->toBeArray();
    expect($component->effects['download']['name'])->toContain('subscription-plans-');
    expect($component->effects['download']['name'])->toContain('.csv');
});

it('logs plan export activity', function (): void {
    $admin = SuperAdmin::factory()->create();

    SubscriptionPlan::create([
        'name' => 'Log Export Plan',
        'slug' => 'log-export-plan',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(PlanIndex::class)
        ->call('exportCsv');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'export_plans',
    ]);
});

it('includes record count in export metadata', function (): void {
    $admin = SuperAdmin::factory()->create();

    SubscriptionPlan::create([
        'name' => 'Metadata Plan',
        'slug' => 'metadata-plan',
        'price_monthly' => 200.00,
        'price_annual' => 2000.00,
        'storage_quota_gb' => 20,
        'support_level' => SupportLevel::Priority,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(PlanIndex::class)
        ->call('exportCsv');

    $log = \App\Models\SuperAdminActivityLog::where('action', 'export_plans')
        ->latest()
        ->first();

    expect($log->metadata)->toBeArray();
    expect($log->metadata)->toHaveKey('record_count');
});

// ============================================
// Activity Log Export Tests
// ============================================

it('can export activity logs to CSV', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Create some activity logs
    \App\Models\SuperAdminActivityLog::log(
        superAdmin: $admin,
        action: 'test_action',
        description: 'Test activity for export',
    );

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(ActivityLogs::class)
        ->call('exportCsv');

    expect($component->effects['download'])->toBeArray();
    expect($component->effects['download']['name'])->toContain('activity-logs-');
    expect($component->effects['download']['name'])->toContain('.csv');
});

it('logs activity log export', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(ActivityLogs::class)
        ->call('exportCsv');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'export_activity_logs',
    ]);
});

it('can filter activity logs by date range', function (): void {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(ActivityLogs::class)
        ->set('startDate', '2024-01-01')
        ->set('endDate', '2024-12-31');

    expect($component->get('startDate'))->toBe('2024-01-01');
    expect($component->get('endDate'))->toBe('2024-12-31');
});

it('exports activity logs with applied filters in metadata', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(ActivityLogs::class)
        ->set('search', 'test')
        ->set('startDate', '2024-01-01')
        ->call('exportCsv');

    $log = \App\Models\SuperAdminActivityLog::where('action', 'export_activity_logs')
        ->latest()
        ->first();

    expect($log->metadata)->toBeArray();
    expect($log->metadata)->toHaveKey('filters');
    expect($log->metadata['filters']['search'])->toBe('test');
    expect($log->metadata['filters']['start_date'])->toBe('2024-01-01');
});

it('can filter activity logs by action type', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Create logs with different actions
    \App\Models\SuperAdminActivityLog::log(
        superAdmin: $admin,
        action: 'tenant_created',
        description: 'Created a tenant',
    );

    \App\Models\SuperAdminActivityLog::log(
        superAdmin: $admin,
        action: 'tenant_suspended',
        description: 'Suspended a tenant',
    );

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(ActivityLogs::class)
        ->set('action', 'tenant_created');

    expect($component->get('action'))->toBe('tenant_created');
});

// ============================================
// Revenue Export Tests
// ============================================

it('can export revenue data to CSV', function (): void {
    $admin = SuperAdmin::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'Revenue Export Plan',
        'slug' => 'revenue-export-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
    ]);

    DB::table('tenants')->insert([
        'id' => 'revenue-export-tenant-1',
        'name' => 'Revenue Export Church',
        'status' => TenantStatus::Active->value,
        'subscription_id' => $plan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->call('exportCsv');

    expect($component->effects['download'])->toBeArray();
    expect($component->effects['download']['name'])->toContain('revenue-report-');
    expect($component->effects['download']['name'])->toContain('.csv');
});

it('logs revenue export activity', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->call('exportCsv');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'export_revenue',
    ]);
});

it('includes MRR and ARR in revenue export metadata', function (): void {
    $admin = SuperAdmin::factory()->create();

    $plan = SubscriptionPlan::create([
        'name' => 'MRR Test Export Plan',
        'slug' => 'mrr-test-export-plan',
        'price_monthly' => 150.00,
        'price_annual' => 1500.00,
        'storage_quota_gb' => 15,
        'support_level' => SupportLevel::Priority,
    ]);

    DB::table('tenants')->insert([
        'id' => 'mrr-export-tenant-1',
        'name' => 'MRR Export Church',
        'status' => TenantStatus::Active->value,
        'subscription_id' => $plan->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->call('exportCsv');

    $log = \App\Models\SuperAdminActivityLog::where('action', 'export_revenue')
        ->latest()
        ->first();

    expect($log->metadata)->toBeArray();
    expect($log->metadata)->toHaveKey('mrr');
    expect($log->metadata)->toHaveKey('arr');
    expect($log->metadata)->toHaveKey('plan_count');
});

it('exports revenue data for all active plans', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Create multiple active plans
    SubscriptionPlan::create([
        'name' => 'Basic Revenue Plan',
        'slug' => 'basic-revenue-plan',
        'price_monthly' => 50.00,
        'price_annual' => 500.00,
        'storage_quota_gb' => 5,
        'support_level' => SupportLevel::Community,
        'is_active' => true,
    ]);

    SubscriptionPlan::create([
        'name' => 'Pro Revenue Plan',
        'slug' => 'pro-revenue-plan',
        'price_monthly' => 100.00,
        'price_annual' => 1000.00,
        'storage_quota_gb' => 10,
        'support_level' => SupportLevel::Email,
        'is_active' => true,
    ]);

    // Create an inactive plan (should be excluded)
    SubscriptionPlan::create([
        'name' => 'Inactive Revenue Plan',
        'slug' => 'inactive-revenue-plan',
        'price_monthly' => 200.00,
        'price_annual' => 2000.00,
        'storage_quota_gb' => 20,
        'support_level' => SupportLevel::Priority,
        'is_active' => false,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(RevenueDashboard::class)
        ->call('exportCsv');

    $log = \App\Models\SuperAdminActivityLog::where('action', 'export_revenue')
        ->latest()
        ->first();

    // Plan count should only include active plans
    expect($log->metadata['plan_count'])->toBeGreaterThanOrEqual(2);
});

// ============================================
// Authorization Tests
// ============================================

it('requires authentication to export tenants', function (): void {
    $this->get(route('superadmin.tenants.index'))
        ->assertRedirect(route('superadmin.login'));
});

it('requires authentication to export plans', function (): void {
    $this->get(route('superadmin.plans.index'))
        ->assertRedirect(route('superadmin.login'));
});

it('requires authentication to export activity logs', function (): void {
    $this->get(route('superadmin.activity-logs'))
        ->assertRedirect(route('superadmin.login'));
});

it('requires authentication to export revenue', function (): void {
    $this->get(route('superadmin.revenue'))
        ->assertRedirect(route('superadmin.login'));
});
