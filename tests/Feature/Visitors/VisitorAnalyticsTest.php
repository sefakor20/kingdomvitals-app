<?php

use App\Enums\BranchRole;
use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Enums\VisitorStatus;
use App\Livewire\Visitors\VisitorAnalytics;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create a subscription plan with visitors module enabled
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['visitors', 'members'],
    ]);    // Initialize tenancy and run migrations    // Clear cached plan data
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);

    // Configure app URL and host for tenant domain routing    // Load routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// ACCESS CONTROL TESTS
// ============================================

test('admin can access visitor analytics', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Visitor Analytics');
});

test('manager can access visitor analytics', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('staff can access visitor analytics', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('volunteer can access visitor analytics', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('user without branch access cannot access visitor analytics', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertForbidden();
});

// ============================================
// PERIOD SELECTION TESTS
// ============================================

test('default period is 90 days', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->assertSet('period', 90);
});

test('can change period', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch])
        ->call('setPeriod', 30)
        ->assertSet('period', 30)
        ->assertDispatched('charts-updated');
});

// ============================================
// SUMMARY STATS TESTS
// ============================================

test('summary stats shows correct total visitors count', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create visitors within period
    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::New,
    ]);

    // Create visitors outside period
    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(100),
        'status' => VisitorStatus::New,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);

    expect($component->get('summaryStats')['total_visitors'])->toBe(5);
});

test('summary stats shows correct conversion rate', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create 10 visitors, 3 converted
    Visitor::factory()->count(7)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::Converted,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);

    expect($component->get('summaryStats')['total_visitors'])->toBe(10);
    expect($component->get('summaryStats')['converted_visitors'])->toBe(3);
    expect($component->get('summaryStats')['conversion_rate'])->toBe(30.0);
});

test('summary stats shows zero conversion rate when no visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);

    expect($component->get('summaryStats')['total_visitors'])->toBe(0);
    expect($component->get('summaryStats')['conversion_rate'])->toBe(0);
});

// ============================================
// CONVERSION FUNNEL TESTS
// ============================================

test('conversion funnel shows correct counts per status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create visitors with different statuses
    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::FollowedUp,
    ]);

    Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::Converted,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $funnelData = $component->get('conversionFunnelData');

    expect($funnelData['labels'])->toContain('New', 'Followed Up', 'Converted');
    expect($funnelData['data'][0])->toBe(5); // New
    expect($funnelData['data'][1])->toBe(3); // FollowedUp
    expect($funnelData['data'][3])->toBe(2); // Converted
});

// ============================================
// FOLLOW-UP EFFECTIVENESS TESTS
// ============================================

test('follow-up effectiveness shows data by type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
    ]);

    // Create follow-ups with different types and outcomes
    VisitorFollowUp::factory()->count(3)->create([
        'visitor_id' => $visitor->id,
        'type' => FollowUpType::Call,
        'outcome' => FollowUpOutcome::Successful,
        'completed_at' => now()->subDays(20),
    ]);

    VisitorFollowUp::factory()->count(2)->create([
        'visitor_id' => $visitor->id,
        'type' => FollowUpType::Call,
        'outcome' => FollowUpOutcome::NoAnswer,
        'completed_at' => now()->subDays(20),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $effectivenessData = $component->get('followUpEffectivenessData');

    expect($effectivenessData['labels'])->toContain('Call');
    // Call type: 3 successful out of 5 total = 60% success rate
    $callIndex = array_search('Call', $effectivenessData['labels']);
    expect($effectivenessData['total_attempts'][$callIndex])->toBe(5);
    expect($effectivenessData['successful'][$callIndex])->toBe(3);
    expect($effectivenessData['success_rates'][$callIndex])->toBe(60.0);
});

// ============================================
// VISITOR SOURCE ANALYSIS TESTS
// ============================================

test('visitor source analysis groups by how did you hear', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create visitors with different sources
    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'how_did_you_hear' => 'Friend',
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'how_did_you_hear' => 'Social Media',
        'status' => VisitorStatus::Converted,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $sourceData = $component->get('visitorSourceData');

    expect($sourceData['labels'])->toContain('Friend', 'Social Media');
    expect($sourceData['table_data'])->toHaveCount(2);
});

// ============================================
// VISITORS OVER TIME TESTS
// ============================================

test('visitors over time returns 12 weeks of data', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $timeData = $component->get('visitorsOverTimeData');

    expect($timeData['labels'])->toHaveCount(12);
    expect($timeData['visitors'])->toHaveCount(12);
    expect($timeData['converted'])->toHaveCount(12);
});

// ============================================
// RECENT CONVERSIONS TESTS
// ============================================

test('recent conversions shows converted visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::Converted,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $recentConversions = $component->get('recentConversions');

    expect($recentConversions)->toHaveCount(3);
});

test('recent conversions limits to 5 results', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Visitor::factory()->count(10)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::Converted,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);
    $recentConversions = $component->get('recentConversions');

    expect($recentConversions)->toHaveCount(5);
});

// ============================================
// VISITOR GROWTH TESTS
// ============================================

test('visitor growth calculates percentage change correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create 10 visitors in current period (last 90 days)
    Visitor::factory()->count(10)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(30),
        'status' => VisitorStatus::New,
    ]);

    // Create 5 visitors in previous period (90-180 days ago)
    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subDays(120),
        'status' => VisitorStatus::New,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorAnalytics::class, ['branch' => $this->branch]);

    // 10 current vs 5 previous = 100% growth
    expect($component->get('summaryStats')['visitor_growth'])->toBe(100.0);
});
