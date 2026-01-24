<?php

use App\Enums\BranchRole;
use App\Enums\DutyRosterStatus;
use App\Livewire\DutyRosters\DutyRosterIndex;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create a subscription plan with duty_roster module enabled
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['duty_roster', 'members', 'clusters'],
    ]);

    // Create a test tenant with the subscription plan
    $this->tenant = Tenant::create([
        'name' => 'Test Church',
        'subscription_id' => $plan->id,
    ]);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    // Initialize tenancy and run migrations
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Clear any cached plan data and re-bind PlanAccessService
    \Illuminate\Support\Facades\Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// Note: HTTP-based page access tests are skipped because tenant routes
// are not registered during tests (domain-based routing limitation).
// Authorization is tested via Livewire component tests below.
// ============================================

// ============================================
// VIEW DUTY ROSTERS AUTHORIZATION TESTS
// ============================================

test('admin can view duty rosters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'Test Theme',
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSee('Test Theme');
});

test('staff can view duty rosters list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'Staff View Theme',
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSee('Staff View Theme');
});

// ============================================
// CREATE DUTY ROSTER AUTHORIZATION TESTS
// ============================================

test('admin can create a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('service_date', now()->format('Y-m-d'))
        ->set('theme', 'REJOICE, THE LORD DELIVERS')
        ->set('preacher_name', 'Rev. John Doe')
        ->set('liturgist_name', 'Cat. Jane Smith')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('roster-created');

    expect(DutyRoster::where('theme', 'REJOICE, THE LORD DELIVERS')->exists())->toBeTrue();
});

test('manager can create a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('service_date', now()->format('Y-m-d'))
        ->set('theme', 'Manager Created Roster')
        ->call('store')
        ->assertHasNoErrors();

    expect(DutyRoster::where('theme', 'Manager Created Roster')->exists())->toBeTrue();
});

test('staff can create a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('service_date', now()->format('Y-m-d'))
        ->set('theme', 'Staff Created Roster')
        ->call('store')
        ->assertHasNoErrors();

    expect(DutyRoster::where('theme', 'Staff Created Roster')->exists())->toBeTrue();
});

test('volunteer cannot create a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE DUTY ROSTER AUTHORIZATION TESTS
// ============================================

test('admin can update a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('edit', $roster)
        ->assertSet('showEditModal', true)
        ->set('theme', 'Updated Theme')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('roster-updated');

    expect($roster->fresh()->theme)->toBe('Updated Theme');
});

test('volunteer cannot update a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('edit', $roster)
        ->assertForbidden();
});

// ============================================
// DELETE DUTY ROSTER AUTHORIZATION TESTS
// ============================================

test('admin can delete a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);
    $rosterId = $roster->id;

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $roster)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('roster-deleted');

    expect(DutyRoster::find($rosterId))->toBeNull();
});

test('manager can delete a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);
    $rosterId = $roster->id;

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $roster)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertHasNoErrors();

    expect(DutyRoster::find($rosterId))->toBeNull();
});

test('staff cannot delete a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $roster)
        ->assertForbidden();
});

// ============================================
// PUBLISH AUTHORIZATION TESTS
// ============================================

test('admin can publish a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('togglePublish', $roster)
        ->assertDispatched('roster-published');

    $roster->refresh();
    expect($roster->is_published)->toBeTrue();
    expect($roster->status)->toBe(DutyRosterStatus::Published);
});

test('manager can publish a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $roster = DutyRoster::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('togglePublish', $roster)
        ->assertDispatched('roster-published');

    expect($roster->fresh()->is_published)->toBeTrue();
});

test('staff cannot publish a duty roster', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $roster = DutyRoster::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('togglePublish', $roster)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search duty rosters by theme', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'REJOICE IN THE LORD',
    ]);

    DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'CALLED TO SERVE',
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->set('search', 'REJOICE')
        ->assertSee('REJOICE IN THE LORD')
        ->assertDontSee('CALLED TO SERVE');
});

test('can filter duty rosters by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    DutyRoster::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'Draft Roster',
    ]);

    DutyRoster::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
        'theme' => 'Published Roster',
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'published')
        ->assertSee('Published Roster')
        ->assertDontSee('Draft Roster');
});

// ============================================
// VALIDATION TESTS
// ============================================

test('service date is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('service_date', '')
        ->set('theme', 'Test Theme')
        ->call('store')
        ->assertHasErrors(['service_date']);
});

test('can create roster with only required fields', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('service_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(DutyRoster::count())->toBe(1);
});

test('can create roster with hymn numbers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('service_date', now()->format('Y-m-d'))
        ->set('hymn_numbers', [1, 313, 65, 75, 648])
        ->call('store')
        ->assertHasNoErrors();

    $roster = DutyRoster::first();
    expect($roster->hymn_numbers)->toBe([1, 313, 65, 75, 648]);
});

test('can create roster with member preacher', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $preacher = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('service_date', now()->format('Y-m-d'))
        ->set('preacher_id', $preacher->id)
        ->call('store')
        ->assertHasNoErrors();

    $roster = DutyRoster::first();
    expect($roster->preacher_id)->toBe($preacher->id);
});

test('can create roster with external preacher name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('service_date', now()->format('Y-m-d'))
        ->set('preacher_name', 'Rev. Guest Minister')
        ->call('store')
        ->assertHasNoErrors();

    $roster = DutyRoster::first();
    expect($roster->preacher_name)->toBe('Rev. Guest Minister');
    expect($roster->preacher_id)->toBeNull();
});

// ============================================
// HYMN NUMBER MANAGEMENT TESTS
// ============================================

test('can add hymn number field', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('hymn_numbers', [])
        ->call('addHymn')
        ->assertCount('hymn_numbers', 1)
        ->call('addHymn')
        ->assertCount('hymn_numbers', 2);
});

test('can remove hymn number field', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->call('addHymn')
        ->call('addHymn')
        ->assertCount('hymn_numbers', 2)
        ->call('removeHymn', 0)
        ->assertCount('hymn_numbers', 1);
});

// ============================================
// MONTH NAVIGATION TESTS
// ============================================

test('can navigate to previous month', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $currentMonth = now()->format('Y-m');
    $previousMonth = now()->subMonth()->format('Y-m');

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSet('monthFilter', $currentMonth)
        ->call('previousMonth')
        ->assertSet('monthFilter', $previousMonth);
});

test('can navigate to next month', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $currentMonth = now()->format('Y-m');
    $nextMonth = now()->addMonth()->format('Y-m');

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSet('monthFilter', $currentMonth)
        ->call('nextMonth')
        ->assertSet('monthFilter', $nextMonth);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('empty state is shown when no duty rosters exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSee('No duty rosters found');
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update duty roster from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $otherBranch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('edit', $roster)
        ->assertForbidden();
});

test('user cannot delete duty roster from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $otherBranch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $roster)
        ->assertForbidden();
});

// ============================================
// CALENDAR VIEW TESTS
// ============================================

test('can toggle to calendar view', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->assertSet('viewMode', 'table')
        ->call('setViewMode', 'calendar')
        ->assertSet('viewMode', 'calendar');
});

test('can toggle back to table view', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('setViewMode', 'calendar')
        ->assertSet('viewMode', 'calendar')
        ->call('setViewMode', 'table')
        ->assertSet('viewMode', 'table');
});

test('calendar data is generated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now()->startOfMonth()->addDays(14),
        'theme' => 'Calendar Test Theme',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('setViewMode', 'calendar');

    // Get the component instance to access computed property
    $calendarData = $component->instance()->calendarData;

    expect($calendarData)->toBeArray();
    expect(count($calendarData))->toBeGreaterThanOrEqual(4); // At least 4 weeks

    // Each week should have 7 days
    foreach ($calendarData as $week) {
        expect(count($week))->toBe(7);
    }
});

test('calendar shows rosters for the correct dates', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $rosterDate = now()->startOfMonth()->addDays(10);
    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => $rosterDate,
        'theme' => 'Mid-Month Theme',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('setViewMode', 'calendar');

    // Get the component instance to access computed property
    $calendarData = $component->instance()->calendarData;

    // Find the day with our roster
    $foundRoster = false;
    foreach ($calendarData as $week) {
        foreach ($week as $day) {
            if ($day['date']->format('Y-m-d') === $rosterDate->format('Y-m-d')) {
                expect($day['roster'])->not->toBeNull();
                expect($day['roster']->theme)->toBe('Mid-Month Theme');
                $foundRoster = true;
                break 2;
            }
        }
    }

    expect($foundRoster)->toBeTrue();
});

test('can create roster for specific date from calendar', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $specificDate = now()->format('Y-m-d');

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('createForDate', $specificDate)
        ->assertSet('showCreateModal', true)
        ->assertSet('service_date', $specificDate);
});

test('calendar view shows day headers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_date' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('setViewMode', 'calendar')
        ->assertSee('Sun')
        ->assertSee('Mon')
        ->assertSee('Tue')
        ->assertSee('Wed')
        ->assertSee('Thu')
        ->assertSee('Fri')
        ->assertSee('Sat');
});

test('calendar view displays even when no rosters exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DutyRosterIndex::class, ['branch' => $this->branch])
        ->call('setViewMode', 'calendar');

    // Get the component instance to access computed property
    $calendarData = $component->instance()->calendarData;
    expect($calendarData)->toBeArray();
    expect(count($calendarData))->toBeGreaterThanOrEqual(4);
});
