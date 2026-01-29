<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\MembershipStatus;
use App\Livewire\Attendance\AttendanceAnalytics;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create a subscription plan with attendance module enabled
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['attendance', 'members', 'visitors'],
    ]);    // Clear cached plan data
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);    // Load routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => 100,
    ]);

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
// PAGE ACCESS TESTS
// ============================================

test('admin can access attendance analytics', function (): void {
    Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->assertOk();
});

test('manager can access attendance analytics', function (): void {
    $manager = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $manager->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    Livewire::actingAs($manager)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->assertOk();
});

test('staff can access attendance analytics', function (): void {
    $staff = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Livewire::actingAs($staff)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->assertOk();
});

test('volunteer can access attendance analytics', function (): void {
    $volunteer = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $volunteer->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    Livewire::actingAs($volunteer)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->assertOk();
});

test('user without branch access cannot view attendance analytics', function (): void {
    $otherUser = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $otherUser->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    Livewire::actingAs($otherUser)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->assertForbidden();
});

// ============================================
// SUMMARY STATS TESTS
// ============================================

test('summary stats calculate correctly', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    // Create attendance records in current period
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $member1->id,
        'date' => now()->subDays(5),
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $member2->id,
        'date' => now()->subDays(5),
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'visitor_id' => $visitor->id,
        'date' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    $component->assertSee('3'); // Total attendance
    $component->assertSee('2'); // Unique members
});

test('engagement metrics show regular and casual attenders', function (): void {
    // Create service dates
    $dates = [
        now()->subDays(7),
        now()->subDays(14),
        now()->subDays(21),
        now()->subDays(28),
    ];

    // Regular attender (attends all 4 dates = 100%)
    $regularMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);
    foreach ($dates as $date) {
        Attendance::factory()->create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'member_id' => $regularMember->id,
            'date' => $date,
        ]);
    }

    // Casual attender (attends 2 of 4 dates = 50%)
    $casualMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $casualMember->id,
        'date' => $dates[0],
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $casualMember->id,
        'date' => $dates[1],
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see engagement metrics section
    $component->assertSee('Regular Attenders');
    $component->assertSee('Casual Attenders');
});

test('lapsed members are detected', function (): void {
    // Lapsed member: attended 5 weeks ago, nothing in last 4 weeks
    $lapsedMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $lapsedMember->id,
        'date' => now()->subWeeks(5),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see lapsed section
    $component->assertSee('Lapsed');
});

// ============================================
// SERVICE UTILIZATION TESTS
// ============================================

test('service utilization is displayed', function (): void {
    // Create attendance records
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $member->id,
        'date' => now()->subDays(7),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see service utilization section
    $component->assertSee('Service Utilization');
    $component->assertSee($this->service->name);
});

// ============================================
// ATTENDANCE TREND TESTS
// ============================================

test('attendance trend chart is displayed', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see trend chart section
    $component->assertSee('Weekly Attendance Trend');
});

// ============================================
// VISITOR CONVERSION TESTS
// ============================================

test('visitor conversion metrics are shown', function (): void {
    // Visitor who came twice (returning)
    $returningVisitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'visitor_id' => $returningVisitor->id,
        'date' => now()->subDays(14),
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'visitor_id' => $returningVisitor->id,
        'date' => now()->subDays(7),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see visitor metrics
    $component->assertSee('Visitors');
    $component->assertSee('conversion rate');
});

// ============================================
// ENGAGEMENT ALERTS TESTS
// ============================================

test('engagement alerts section is displayed', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see engagement alerts tabs
    $component->assertSee('Engagement Alerts');
    $component->assertSee('At-Risk');
});

// ============================================
// MEMBER ENGAGEMENT LIST TESTS
// ============================================

test('member engagement list is displayed', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
        'first_name' => 'John',
        'last_name' => 'TestMember',
    ]);

    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $member->id,
        'date' => now()->subDays(7),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch]);

    // Should see member engagement table
    $component->assertSee('Member Engagement Scores');
    $component->assertSee('John');
    $component->assertSee('TestMember');
});

test('member search filters the list', function (): void {
    $john = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $jane = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $john->id,
        'date' => now()->subDays(7),
    ]);
    Attendance::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'member_id' => $jane->id,
        'date' => now()->subDays(7),
    ]);

    // Search for "John"
    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->set('memberSearch', 'John');

    $component->assertSee('John');
    $component->assertDontSee('Jane');
});

// ============================================
// PERIOD FILTERING TESTS
// ============================================

test('period can be changed', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->call('setPeriod', 30)
        ->assertSet('period', 30);
});

// ============================================
// SERVICE FILTERING TESTS
// ============================================

test('service filter can be set', function (): void {
    $component = Livewire::actingAs($this->admin)
        ->test(AttendanceAnalytics::class, ['branch' => $this->branch])
        ->set('serviceFilter', $this->service->id)
        ->assertSet('serviceFilter', $this->service->id);
});
