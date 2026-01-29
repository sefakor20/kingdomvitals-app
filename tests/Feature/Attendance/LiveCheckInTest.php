<?php

use App\Enums\BranchRole;
use App\Enums\CheckInMethod;
use App\Livewire\Attendance\LiveCheckIn;
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

test('authenticated user with staff access can view live check-in page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services/{$this->service->id}/check-in")
        ->assertOk()
        ->assertSeeLivewire(LiveCheckIn::class);
});

test('volunteer cannot access live check-in page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services/{$this->service->id}/check-in")
        ->assertForbidden();
});

test('unauthenticated user cannot access live check-in page', function (): void {
    $this->get("/branches/{$this->branch->id}/services/{$this->service->id}/check-in")
        ->assertRedirect('/login');
});

// ============================================
// SEARCH TESTS
// ============================================

test('can search for members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'UniqueFirstName',
        'last_name' => 'UniqueLastName',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', 'UniqueFirstName');

    $results = $component->instance()->searchResults;
    expect($results->count())->toBe(1);
    expect($results->first()['name'])->toContain('UniqueFirstName');
    expect($results->first()['type'])->toBe('member');
});

test('can search for visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'SpecialVisitor',
        'last_name' => 'Name',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', 'SpecialVisitor');

    $results = $component->instance()->searchResults;
    expect($results->count())->toBe(1);
    expect($results->first()['name'])->toBe('SpecialVisitor Name');
    expect($results->first()['type'])->toBe('visitor');
});

test('search requires at least 2 characters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', 'A');

    $results = $component->instance()->searchResults;
    expect($results->count())->toBe(0);
});

test('search shows both members and visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'TestName',
        'last_name' => 'Member',
    ]);
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'TestName',
        'last_name' => 'Visitor',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', 'TestName');

    $results = $component->instance()->searchResults;
    expect($results->count())->toBe(2);
    expect($results->pluck('type')->toArray())->toContain('member', 'visitor');
});

// ============================================
// CHECK-IN TESTS
// ============================================

test('can check in a member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('checkIn', $this->member->id, 'member')
        ->assertDispatched('check-in-success');

    $attendance = Attendance::where('service_id', $this->service->id)
        ->where('member_id', $this->member->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Kiosk);
});

test('can check in a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('checkIn', $this->visitor->id, 'visitor')
        ->assertDispatched('check-in-success');

    $attendance = Attendance::where('service_id', $this->service->id)
        ->where('visitor_id', $this->visitor->id)
        ->first();

    expect($attendance)->not->toBeNull();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Kiosk);
});

test('cannot check in same person twice on same day', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create existing attendance
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('checkIn', $this->member->id, 'member')
        ->assertDispatched('already-checked-in');

    // Should still only have 1 attendance record
    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('check in clears search query', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', $this->member->first_name)
        ->call('checkIn', $this->member->id, 'member')
        ->assertSet('searchQuery', '');
});

// ============================================
// STATS TESTS
// ============================================

test('today stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create some attendance records
    Attendance::factory()->count(2)->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => fn () => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'visitor_id' => null,
        'date' => now()->format('Y-m-d'),
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => null,
        'visitor_id' => fn () => Visitor::factory()->create(['branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service]);
    $stats = $component->instance()->todayStats;

    expect($stats['total'])->toBe(3);
    expect($stats['members'])->toBe(2);
    expect($stats['visitors'])->toBe(1);
});

// ============================================
// RECENT CHECK-INS TESTS
// ============================================

test('recent check-ins shows last 10 entries', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create 12 attendance records
    for ($i = 0; $i < 12; $i++) {
        Attendance::factory()->create([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'member_id' => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
            'date' => now()->format('Y-m-d'),
            'check_in_time' => now()->addMinutes($i)->format('H:i'),
        ]);
    }

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service]);
    $recentCheckIns = $component->instance()->recentCheckIns;

    expect($recentCheckIns->count())->toBe(10);
});

// ============================================
// ALREADY CHECKED IN INDICATOR TESTS
// ============================================

test('search results indicate already checked in status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'AlreadyChecked',
        'last_name' => 'In',
    ]);

    // Create existing attendance
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->set('searchQuery', 'AlreadyChecked');

    $results = $component->instance()->searchResults;
    expect($results->count())->toBe(1);
    expect($results->first()['already_checked_in'])->toBeTrue();
});

// ============================================
// CHECK-OUT TESTS
// ============================================

test('can check out a member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->format('Y-m-d'),
        'check_out_time' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('checkOut', $attendance->id)
        ->assertDispatched('check-out-success');

    $attendance->refresh();
    expect($attendance->check_out_time)->not->toBeNull();
});

test('cannot check out already checked out attendance', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->format('Y-m-d'),
        'check_out_time' => '15:00',
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('checkOut', $attendance->id)
        ->assertNotDispatched('check-out-success');

    $attendance->refresh();
    expect($attendance->check_out_time)->toBe('15:00');
});

test('recent check-ins includes check-out status', function (): void {
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
        'date' => now()->format('Y-m-d'),
        'check_in_time' => '09:00',
        'check_out_time' => '10:30',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service]);
    $recentCheckIns = $component->instance()->recentCheckIns;

    expect($recentCheckIns->first()['is_checked_out'])->toBeTrue();
    expect($recentCheckIns->first()['check_out_time'])->toBe('10:30');
});

test('today stats includes checked out count', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create 3 attendance records, 2 checked out
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
        'check_out_time' => '10:00',
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
        'check_out_time' => '11:00',
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
        'check_out_time' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, ['branch' => $this->branch, 'service' => $this->service]);
    $stats = $component->instance()->todayStats;

    expect($stats['total'])->toBe(3);
    expect($stats['checked_out'])->toBe(2);
});
