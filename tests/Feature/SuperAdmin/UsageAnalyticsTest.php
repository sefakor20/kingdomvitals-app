<?php

declare(strict_types=1);

use App\Enums\SuperAdminRole;
use App\Enums\TenantStatus;
use App\Livewire\SuperAdmin\Analytics\UsageAnalytics;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdmin;
use App\Models\TenantUsageSnapshot;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

describe('page access', function (): void {
    it('allows owner to view usage analytics page', function (): void {
        $owner = SuperAdmin::factory()->owner()->create();

        $this->actingAs($owner, 'superadmin')
            ->get(route('superadmin.analytics.usage'))
            ->assertOk()
            ->assertSee('Usage Analytics');
    });

    it('allows admin to view usage analytics page', function (): void {
        $admin = SuperAdmin::factory()->create(['role' => SuperAdminRole::Admin]);

        $this->actingAs($admin, 'superadmin')
            ->get(route('superadmin.analytics.usage'))
            ->assertOk()
            ->assertSee('Usage Analytics');
    });

    it('allows support to view usage analytics page', function (): void {
        $support = SuperAdmin::factory()->create(['role' => SuperAdminRole::Support]);

        $this->actingAs($support, 'superadmin')
            ->get(route('superadmin.analytics.usage'))
            ->assertOk()
            ->assertSee('Usage Analytics');
    });

    it('denies guest access to usage analytics page', function (): void {
        $this->get(route('superadmin.analytics.usage'))
            ->assertRedirect(route('superadmin.login'));
    });
});

describe('overview stats', function (): void {
    it('displays key usage metrics', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Total Tenants')
            ->assertSee('Active')
            ->assertSee('Total Members')
            ->assertSee('SMS Sent')
            ->assertSee('Donations')
            ->assertSee('Avg Members');
    });

    it('shows correct tenant counts', function (): void {
        $admin = SuperAdmin::factory()->create();

        // Create test tenants
        DB::table('tenants')->insert([
            'id' => 'analytics-active-1',
            'name' => 'Active Church',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => 'analytics-trial-1',
            'name' => 'Trial Church',
            'status' => TenantStatus::Trial->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create snapshots so they appear in top tenants table
        TenantUsageSnapshot::create([
            'tenant_id' => 'analytics-active-1',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 50,
            'active_members' => 50,
            'total_branches' => 1,
            'sms_sent_this_month' => 10,
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'analytics-trial-1',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 30,
            'active_members' => 30,
            'total_branches' => 1,
            'sms_sent_this_month' => 5,
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Active Church')
            ->assertSee('Trial Church');
    });

    it('shows aggregated member totals from snapshots', function (): void {
        $admin = SuperAdmin::factory()->create();

        DB::table('tenants')->insert([
            'id' => 'snapshot-tenant-1',
            'name' => 'Snapshot Church 1',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenants')->insert([
            'id' => 'snapshot-tenant-2',
            'name' => 'Snapshot Church 2',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'snapshot-tenant-1',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 100,
            'active_members' => 80,
            'total_branches' => 2,
            'sms_sent_this_month' => 50,
            'donations_this_month' => 5000.00,
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'snapshot-tenant-2',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 200,
            'active_members' => 180,
            'total_branches' => 3,
            'sms_sent_this_month' => 100,
            'donations_this_month' => 10000.00,
        ]);

        // Verify totals are displayed - 80 + 180 = 260 members, 50 + 100 = 150 SMS
        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('260')  // Total members
            ->assertSee('150'); // Total SMS
    });
});

describe('quota alerts', function (): void {
    it('shows tenants approaching member quota limits', function (): void {
        $admin = SuperAdmin::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Limited Plan',
            'slug' => 'limited-plan-'.uniqid(),
            'max_members' => 100,
            'price_monthly' => 50.00,
            'price_annual' => 500.00,
            'storage_quota_gb' => 5,
            'sms_credits_monthly' => 100,
            'support_level' => \App\Enums\SupportLevel::Community,
        ]);

        DB::table('tenants')->insert([
            'id' => 'quota-alert-tenant',
            'name' => 'Quota Alert Church',
            'status' => TenantStatus::Active->value,
            'subscription_id' => $plan->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'quota-alert-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 85,
            'active_members' => 85,
            'total_branches' => 1,
            'sms_sent_this_month' => 10,
            'member_quota_usage_percent' => 85.00,
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Quota Alerts')
            ->assertSee('Quota Alert Church');
    });

    it('shows all clear when no tenants are approaching limits', function (): void {
        $admin = SuperAdmin::factory()->create();

        DB::table('tenants')->insert([
            'id' => 'safe-tenant',
            'name' => 'Safe Church',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'safe-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 50,
            'active_members' => 50,
            'total_branches' => 1,
            'sms_sent_this_month' => 10,
            'member_quota_usage_percent' => 50.00,
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('All tenants within limits');
    });
});

describe('feature adoption', function (): void {
    it('shows feature adoption statistics', function (): void {
        $admin = SuperAdmin::factory()->create();

        DB::table('tenants')->insert([
            'id' => 'feature-tenant',
            'name' => 'Feature Church',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'feature-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 100,
            'active_members' => 100,
            'total_branches' => 1,
            'sms_sent_this_month' => 50,
            'active_modules' => ['members', 'donations', 'sms'],
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Feature Adoption')
            ->assertSee('Members')
            ->assertSee('Donations')
            ->assertSee('SMS');
    });
});

describe('top tenants', function (): void {
    it('shows top tenants by members', function (): void {
        $admin = SuperAdmin::factory()->create();

        DB::table('tenants')->insert([
            'id' => 'large-tenant',
            'name' => 'Large Church',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'large-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 500,
            'active_members' => 450,
            'total_branches' => 5,
            'sms_sent_this_month' => 200,
            'donations_this_month' => 25000.00,
            'attendance_this_month' => 1000,
        ]);

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Top Tenants')
            ->assertSee('Large Church');
    });

    it('can sort top tenants by different columns', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSet('sortBy', 'active_members')
            ->call('sortBy', 'sms_sent_this_month')
            ->assertSet('sortBy', 'sms_sent_this_month')
            ->assertSet('sortDirection', 'desc');
    });

    it('toggles sort direction when clicking same column', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSet('sortDirection', 'desc')
            ->call('sortBy', 'active_members')
            ->assertSet('sortDirection', 'asc');
    });
});

describe('period filtering', function (): void {
    it('can change the reporting period', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSet('period', 30)
            ->call('setPeriod', 7)
            ->assertSet('period', 7);
    });

    it('displays period label correctly', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->assertSee('Last 30 days');
    });
});

describe('export', function (): void {
    it('can export usage analytics to CSV', function (): void {
        $admin = SuperAdmin::factory()->create();

        $component = Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->call('exportCsv');

        expect($component->effects['download'])->toBeArray();
        expect($component->effects['download']['name'])->toContain('usage-analytics-');
        expect($component->effects['download']['name'])->toContain('.csv');
    });

    it('logs export activity', function (): void {
        $admin = SuperAdmin::factory()->create();

        Livewire::actingAs($admin, 'superadmin')
            ->test(UsageAnalytics::class)
            ->call('exportCsv');

        $this->assertDatabaseHas('super_admin_activity_logs', [
            'super_admin_id' => $admin->id,
            'action' => 'export_usage_analytics',
        ]);
    });
});

describe('TenantUsageSnapshot model', function (): void {
    it('has correct fillable attributes', function (): void {
        $snapshot = new TenantUsageSnapshot;

        expect($snapshot->getFillable())->toContain('tenant_id');
        expect($snapshot->getFillable())->toContain('total_members');
        expect($snapshot->getFillable())->toContain('active_modules');
        expect($snapshot->getFillable())->toContain('snapshot_date');
    });

    it('casts active_modules to array', function (): void {
        DB::table('tenants')->insert([
            'id' => 'cast-test-tenant',
            'name' => 'Cast Test',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshot = TenantUsageSnapshot::create([
            'tenant_id' => 'cast-test-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 100,
            'active_members' => 100,
            'total_branches' => 1,
            'sms_sent_this_month' => 0,
            'active_modules' => ['members', 'donations'],
        ]);

        expect($snapshot->active_modules)->toBeArray();
        expect($snapshot->active_modules)->toContain('members');
    });

    it('returns correct quota alerts', function (): void {
        DB::table('tenants')->insert([
            'id' => 'alert-model-test',
            'name' => 'Alert Model Test',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshot = TenantUsageSnapshot::create([
            'tenant_id' => 'alert-model-test',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 85,
            'active_members' => 85,
            'total_branches' => 1,
            'sms_sent_this_month' => 0,
            'member_quota_usage_percent' => 85.00,
            'sms_quota_usage_percent' => 50.00,
        ]);

        $alerts = $snapshot->getQuotaAlerts(80);

        expect($alerts)->toHaveCount(1);
        expect($alerts[0]['type'])->toBe('members');
        expect($alerts[0]['usage'])->toBe(85.0);
    });

    it('uses forToday scope correctly', function (): void {
        DB::table('tenants')->insert([
            'id' => 'scope-test-tenant',
            'name' => 'Scope Test',
            'status' => TenantStatus::Active->value,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'scope-test-tenant',
            'snapshot_date' => now()->toDateString(),
            'total_members' => 100,
            'active_members' => 100,
            'total_branches' => 1,
            'sms_sent_this_month' => 0,
        ]);

        TenantUsageSnapshot::create([
            'tenant_id' => 'scope-test-tenant',
            'snapshot_date' => now()->subDay()->toDateString(),
            'total_members' => 90,
            'active_members' => 90,
            'total_branches' => 1,
            'sms_sent_this_month' => 0,
        ]);

        $todaySnapshots = TenantUsageSnapshot::forToday()->get();

        expect($todaySnapshots)->toHaveCount(1);
        expect($todaySnapshots->first()->total_members)->toBe(100);
    });
});
