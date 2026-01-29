<?php

use App\Enums\BranchRole;
use App\Enums\CheckInMethod;
use App\Enums\HouseholdRole;
use App\Livewire\Attendance\LiveCheckIn;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// QR CODE CHECK-IN TESTS
// ============================================

test('can check in member via qr code', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $token = $member->generateQrToken();

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->dispatch('qr-scanned', code: $token)
        ->assertDispatched('check-in-success');

    expect(Attendance::where('member_id', $member->id)->exists())->toBeTrue();

    $attendance = Attendance::where('member_id', $member->id)->first();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Qr);
});

test('shows error for invalid qr code', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->dispatch('qr-scanned', code: 'invalid-token')
        ->assertDispatched('qr-error')
        ->assertSet('qrError', __('Invalid QR code. Please try again.'));
});

test('shows error for member from different branch', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $otherBranch = Branch::factory()->create();
    $member = Member::factory()->create([
        'primary_branch_id' => $otherBranch->id,
        'status' => 'active',
    ]);

    $token = $member->generateQrToken();

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->dispatch('qr-scanned', code: $token)
        ->assertDispatched('qr-error')
        ->assertSet('qrError', __('This member belongs to a different branch.'));
});

test('shows error for inactive member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'inactive',
    ]);

    $token = $member->generateQrToken();

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->dispatch('qr-scanned', code: $token)
        ->assertDispatched('qr-error')
        ->assertSet('qrError', __('This member is not active.'));
});

test('shows error for already checked in member via qr', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $token = $member->generateQrToken();

    // Pre-check in the member
    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->dispatch('qr-scanned', code: $token)
        ->assertDispatched('already-checked-in');
});

// ============================================
// TAB SWITCHING TESTS
// ============================================

test('can switch between tabs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->assertSet('activeTab', 'search')
        ->call('setActiveTab', 'qr')
        ->assertSet('activeTab', 'qr')
        ->call('setActiveTab', 'family')
        ->assertSet('activeTab', 'family')
        ->call('setActiveTab', 'search')
        ->assertSet('activeTab', 'search');
});

test('switching away from qr tab stops scanning', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('setActiveTab', 'qr')
        ->call('startScanning')
        ->assertSet('isScanning', true)
        ->call('setActiveTab', 'search')
        ->assertSet('isScanning', false);
});

// ============================================
// SEARCH CHECK-IN TESTS
// ============================================

test('can search and check in member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->set('searchQuery', 'John')
        ->call('checkIn', $member->id, 'member')
        ->assertDispatched('check-in-success');

    expect(Attendance::where('member_id', $member->id)->exists())->toBeTrue();
});

test('cannot check in already checked in member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    // Pre-check in the member
    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('checkIn', $member->id, 'member')
        ->assertDispatched('already-checked-in');
});

// ============================================
// FAMILY CHECK-IN TESTS
// ============================================

test('can search for households', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Smith Family',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('setActiveTab', 'family')
        ->set('familySearchQuery', 'Smith');

    expect($component->instance()->householdSearchResults->count())->toBe(1);
    expect($component->instance()->householdSearchResults->first()->name)->toBe('Smith Family');
});

test('can open family check-in modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('openFamilyModal', $household->id)
        ->assertSet('showFamilyModal', true)
        ->assertSet('selectedHouseholdId', $household->id);
});

test('can check in selected family members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Head,
        'status' => 'active',
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Spouse,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('openFamilyModal', $household->id)
        ->call('checkInSelectedFamily')
        ->assertDispatched('family-check-in-success');

    expect(Attendance::where('member_id', $member1->id)->exists())->toBeTrue();
    expect(Attendance::where('member_id', $member2->id)->exists())->toBeTrue();
});

test('pre-selects unchecked members when opening family modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'active',
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'active',
    ]);

    // Pre-check in member1
    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('openFamilyModal', $household->id);

    // Only member2 should be pre-selected
    expect($component->get('selectedFamilyMembers'))->toContain($member2->id);
    expect($component->get('selectedFamilyMembers'))->not->toContain($member1->id);
});

test('can toggle family member selection', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('openFamilyModal', $household->id);

    // Initially selected
    expect($component->get('selectedFamilyMembers'))->toContain($member->id);

    // Toggle off
    $component->call('toggleFamilyMember', $member->id);
    expect($component->get('selectedFamilyMembers'))->not->toContain($member->id);

    // Toggle on
    $component->call('toggleFamilyMember', $member->id);
    expect($component->get('selectedFamilyMembers'))->toContain($member->id);
});

test('can close family modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->call('openFamilyModal', $household->id)
        ->assertSet('showFamilyModal', true)
        ->call('closeFamilyModal')
        ->assertSet('showFamilyModal', false)
        ->assertSet('selectedHouseholdId', null)
        ->assertSet('selectedFamilyMembers', [])
        ->assertSet('familySearchQuery', '');
});

// ============================================
// STATS TESTS
// ============================================

test('shows correct attendance stats', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create 2 member check-ins
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ]);

    $stats = $component->instance()->todayStats;

    expect($stats['total'])->toBe(2);
    expect($stats['members'])->toBe(2);
    expect($stats['visitors'])->toBe(0);
});

// ============================================
// SCANNING STATE TESTS
// ============================================

test('can start and stop scanning', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(LiveCheckIn::class, [
        'branch' => $this->branch,
        'service' => $this->service,
    ])
        ->assertSet('isScanning', false)
        ->call('startScanning')
        ->assertSet('isScanning', true)
        ->call('stopScanning')
        ->assertSet('isScanning', false);
});
