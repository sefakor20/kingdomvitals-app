<?php

use App\Enums\BranchRole;
use App\Enums\FollowUpOutcome;
use App\Enums\MembershipStatus;
use App\Livewire\Dashboard;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Models\User;
use App\Services\BranchContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// ACCESS TESTS
// ============================================

test('authenticated user can view dashboard', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

test('unauthenticated user is redirected to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

// ============================================
// MEMBER METRICS TESTS
// ============================================

test('displays correct active member count', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create active members
    Member::factory()->count(5)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);

    // Create inactive member (should not be counted)
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Inactive,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->totalActiveMembers)->toBe(5);
});

test('displays new members this month', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create members joined this month
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now(),
    ]);

    // Create member joined last month
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now()->subMonth(),
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->newMembersThisMonth)->toBe(3);
});

// ============================================
// VISITOR METRICS TESTS
// ============================================

test('displays new visitors this month', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create visitors this month
    Visitor::factory()->count(4)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now(),
    ]);

    // Create visitor last month
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now()->subMonth(),
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->newVisitorsThisMonth)->toBe(4);
});

test('calculates correct conversion rate', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create 10 visitors, 3 converted
    Visitor::factory()->count(7)->create(['branch_id' => $this->branch->id]);
    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'is_converted' => true,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->conversionRate)->toBe(30.0);
});

test('conversion rate is zero when no visitors', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->conversionRate)->toBe(0.0);
});

// ============================================
// FOLLOW-UP METRICS TESTS
// ============================================

test('counts overdue follow-ups correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    // Create overdue follow-up
    VisitorFollowUp::factory()->create([
        'visitor_id' => $visitor->id,
        'is_scheduled' => true,
        'scheduled_at' => now()->subDays(2),
        'outcome' => FollowUpOutcome::Pending,
    ]);

    // Create future follow-up (not overdue)
    VisitorFollowUp::factory()->create([
        'visitor_id' => $visitor->id,
        'is_scheduled' => true,
        'scheduled_at' => now()->addDays(2),
        'outcome' => FollowUpOutcome::Pending,
    ]);

    // Create completed follow-up (should not be counted)
    VisitorFollowUp::factory()->create([
        'visitor_id' => $visitor->id,
        'is_scheduled' => true,
        'scheduled_at' => now()->subDay(),
        'outcome' => FollowUpOutcome::Successful,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->overdueFollowUps)->toBe(1);
});

test('pending follow-ups returns top 5 ordered by scheduled date', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    // Create 7 pending follow-ups
    for ($i = 1; $i <= 7; $i++) {
        VisitorFollowUp::factory()->create([
            'visitor_id' => $visitor->id,
            'is_scheduled' => true,
            'scheduled_at' => now()->addDays($i),
            'outcome' => FollowUpOutcome::Pending,
        ]);
    }

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->pendingFollowUps)->toHaveCount(5);
});

// ============================================
// BRANCH SCOPING TESTS
// ============================================

test('metrics are scoped to selected branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create members in current branch
    Member::factory()->count(5)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);

    // Create members in other branch (should not be counted)
    Member::factory()->count(10)->create([
        'primary_branch_id' => $otherBranch->id,
        'status' => MembershipStatus::Active,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->totalActiveMembers)->toBe(5);
});

test('updates metrics when branch is switched', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->count(5)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);
    Member::factory()->count(10)->create([
        'primary_branch_id' => $otherBranch->id,
        'status' => MembershipStatus::Active,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->totalActiveMembers)->toBe(5);

    // Dispatch branch switched event
    $component->dispatch('branch-switched', branchId: $otherBranch->id);

    expect($component->instance()->totalActiveMembers)->toBe(10);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('shows empty state when no branch exists', function () {
    // Delete the main branch created in beforeEach
    $this->branch->delete();

    $user = User::factory()->create();

    // Clear any branch context
    app(BranchContextService::class)->clearContext();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('No Branch Selected');
});

test('shows zero values when branch has no data', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    $component = Livewire::test(Dashboard::class);
    expect($component->instance()->totalActiveMembers)->toBe(0);
    expect($component->instance()->newVisitorsThisMonth)->toBe(0);
    expect($component->instance()->overdueFollowUps)->toBe(0);
    expect($component->instance()->donationsThisMonth)->toBe(0.0);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('dashboard displays all kpi sections', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    app(BranchContextService::class)->setCurrentBranch($this->branch->id);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('Active Members')
        ->assertSee('New Visitors')
        ->assertSee('Overdue Follow-ups')
        ->assertSee('Donations')
        ->assertSee('Quick Actions')
        ->assertSee('Upcoming Follow-ups')
        ->assertSee('Last Service Attendance')
        ->assertSee('Recent Activity');
});
