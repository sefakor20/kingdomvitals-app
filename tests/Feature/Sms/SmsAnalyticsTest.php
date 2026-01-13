<?php

use App\Enums\BranchRole;
use App\Livewire\Sms\SmsAnalytics;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a test tenant
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Create main branch
    $this->branch = Branch::factory()->main()->create();

    // Create user with branch access
    $this->user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// ACCESS TESTS
// ============================================

test('authenticated user can view sms analytics page', function (): void {
    $this->actingAs($this->user)
        ->get(route('sms.analytics', $this->branch))
        ->assertOk()
        ->assertSeeLivewire(SmsAnalytics::class);
});

test('unauthenticated user cannot view sms analytics', function (): void {
    $this->get(route('sms.analytics', $this->branch))
        ->assertRedirect('/login');
});

// ============================================
// COMPONENT TESTS
// ============================================

test('analytics component renders with correct data', function (): void {
    // Create some SMS logs
    SmsLog::factory()->count(5)->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    SmsLog::factory()->count(3)->failed()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('SMS Analytics')
        ->assertSee('Total Messages')
        ->assertSee('Delivery Rate')
        ->assertSee('Failed');
});

test('summary stats are calculated correctly', function (): void {
    // Create 10 SMS: 7 delivered, 2 failed, 1 pending
    SmsLog::factory()->count(7)->delivered()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.10,
        'created_at' => now(),
    ]);

    SmsLog::factory()->count(2)->failed()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.10,
        'created_at' => now(),
    ]);

    SmsLog::factory()->count(1)->pending()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.10,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch]);

    $summaryStats = $component->get('summaryStats');

    expect($summaryStats['total'])->toBe(10);
    expect($summaryStats['delivered'])->toBe(7);
    expect($summaryStats['failed'])->toBe(2);
    expect($summaryStats['delivery_rate'])->toBe(70.0);
    expect($summaryStats['total_cost'])->toBe(1.0);
});

test('period selector changes data range', function (): void {
    // Create SMS logs at different times
    SmsLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(5),
    ]);

    SmsLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(20),
    ]);

    SmsLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(60),
    ]);

    // With 7 day period, should only see 1 message
    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch])
        ->call('setPeriod', 7);

    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(1);

    // With 30 day period, should see 2 messages
    $component->call('setPeriod', 30);
    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(2);

    // With 90 day period, should see all 3 messages
    $component->call('setPeriod', 90);
    $summaryStats = $component->get('summaryStats');
    expect($summaryStats['total'])->toBe(3);
});

test('messages by type data is grouped correctly', function (): void {
    SmsLog::factory()->count(3)->birthday()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    SmsLog::factory()->count(2)->reminder()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch]);

    $messagesByType = $component->get('messagesByTypeData');

    expect($messagesByType['labels'])->toContain('Birthday');
    expect($messagesByType['labels'])->toContain('Reminder');
    expect(count($messagesByType['data']))->toBe(2);
});

test('status distribution data includes correct colors', function (): void {
    SmsLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    SmsLog::factory()->failed()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch]);

    $statusDistribution = $component->get('statusDistributionData');

    expect($statusDistribution['labels'])->toContain('Delivered');
    expect($statusDistribution['labels'])->toContain('Failed');
    expect($statusDistribution['colors'])->toContain('#22c55e'); // green for delivered
    expect($statusDistribution['colors'])->toContain('#ef4444'); // red for failed
});

test('daily cost data is aggregated correctly', function (): void {
    // Create SMS on two different days
    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.25,
        'created_at' => now()->subDays(1),
    ]);

    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.15,
        'created_at' => now()->subDays(1),
    ]);

    SmsLog::factory()->create([
        'branch_id' => $this->branch->id,
        'cost' => 0.30,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch]);

    $dailyCost = $component->get('dailyCostData');

    // Should have data for 2 days
    expect(count($dailyCost['labels']))->toBe(2);
    expect(count($dailyCost['data']))->toBe(2);
});

test('analytics page shows empty state when no data', function (): void {
    Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch])
        ->assertSee('No data available')
        ->assertSee('Send some SMS messages to see analytics here');
});

test('delivery rate data returns correct format', function (): void {
    SmsLog::factory()->delivered()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SmsAnalytics::class, ['branch' => $this->branch]);

    $deliveryRate = $component->get('deliveryRateData');

    expect($deliveryRate)->toHaveKeys(['labels', 'data']);
    expect($deliveryRate['labels'])->toBeArray();
    expect($deliveryRate['data'])->toBeArray();
});
