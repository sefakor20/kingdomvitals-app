<?php

use App\Enums\BranchRole;
use App\Enums\CheckInMethod;
use App\Livewire\Attendance\AttendanceIndex;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $this->member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $this->visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view attendance page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/attendance")
        ->assertOk()
        ->assertSeeLivewire(AttendanceIndex::class);
});

test('user without branch access cannot view attendance page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/attendance")
        ->assertForbidden();
});

test('unauthenticated user cannot view attendance page', function (): void {
    $this->get("/branches/{$this->branch->id}/attendance")
        ->assertRedirect('/login');
});

// ============================================
// VIEW AUTHORIZATION TESTS
// ============================================

test('admin can view attendance list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->assertSee($this->member->fullName());
});

test('volunteer can view attendance list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->assertSee($this->member->fullName());
});

// ============================================
// DELETE AUTHORIZATION TESTS
// ============================================

test('admin can delete attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $attendance)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('attendance-deleted');

    expect(Attendance::find($attendance->id))->toBeNull();
});

test('volunteer cannot delete attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $attendance)
        ->assertForbidden();
});

// ============================================
// FILTER TESTS
// ============================================

test('can filter attendance by search term', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Johnathan',
        'last_name' => 'Smithson',
    ]);
    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
    ]);
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
    ]);

    $this->actingAs($user);

    // Use distinct names to ensure filtering is working
    $component = Livewire::test(AttendanceIndex::class, ['branch' => $this->branch]);

    // First verify both are visible without filter
    $component->assertSee('Johnathan')
        ->assertSee('Jane');

    // Then filter and check results
    $component->set('search', 'Johnathan');

    $records = $component->instance()->attendanceRecords;
    expect($records->count())->toBe(1);
    expect($records->first()->member->first_name)->toBe('Johnathan');
});

test('can filter attendance by service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service2 = Service::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Evening Service']);

    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
    ]);
    Attendance::factory()->create([
        'service_id' => $service2->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('serviceFilter', $this->service->id)
        ->assertSee($member1->fullName())
        ->assertDontSee($member2->fullName());
});

test('can filter attendance by type (member/visitor)', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'visitor_id' => null,
    ]);
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => null,
        'visitor_id' => $this->visitor->id,
    ]);

    $this->actingAs($user);

    // Filter by member
    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', 'member')
        ->assertSee($this->member->fullName())
        ->assertDontSee($this->visitor->fullName());

    // Filter by visitor
    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', 'visitor')
        ->assertDontSee($this->member->fullName())
        ->assertSee($this->visitor->fullName());
});

test('can filter attendance by check-in method', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'check_in_method' => CheckInMethod::Manual,
    ]);
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('methodFilter', 'manual')
        ->assertSee($member1->fullName())
        ->assertDontSee($member2->fullName());
});

test('can filter attendance by date range', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'date' => now()->format('Y-m-d'),
    ]);
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'date' => now()->subDays(10)->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subDays(2)->format('Y-m-d'))
        ->assertSee($member1->fullName())
        ->assertDontSee($member2->fullName());
});

test('can apply quick filter for today', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'date' => now()->format('Y-m-d'),
    ]);
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'date' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->call('applyQuickFilter', 'today')
        ->assertSee($member1->fullName())
        ->assertDontSee($member2->fullName());
});

test('can clear all filters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('search', 'test')
        ->set('serviceFilter', $this->service->id)
        ->set('typeFilter', 'member')
        ->set('quickFilter', 'today')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('serviceFilter', null)
        ->assertSet('typeFilter', '')
        ->assertSet('quickFilter', '');
});

// ============================================
// STATS TESTS
// ============================================

test('attendance stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create member attendance
    Attendance::factory()->count(3)->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => fn () => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'visitor_id' => null,
        'date' => now()->format('Y-m-d'),
    ]);

    // Create visitor attendance
    Attendance::factory()->count(2)->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => null,
        'visitor_id' => fn () => Visitor::factory()->create(['branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
    ]);

    // Create old attendance (not today)
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(AttendanceIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->attendanceStats;

    expect($stats['total'])->toBe(6);
    expect($stats['members'])->toBe(4); // 3 today + 1 old
    expect($stats['visitors'])->toBe(2);
    expect($stats['today'])->toBe(5); // 3 members + 2 visitors today
});

// ============================================
// CSV EXPORT TESTS
// ============================================

test('can export attendance records to csv', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $response = Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->call('exportToCsv');

    expect($response->effects['download'])->not->toBeNull();
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('displays empty state when no records exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->assertSee('No attendance records found');
});

test('displays different empty state message when filters active', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceIndex::class, ['branch' => $this->branch])
        ->set('search', 'nonexistent')
        ->assertSee('Try adjusting your search or filter criteria.');
});
