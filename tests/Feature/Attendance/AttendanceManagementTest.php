<?php

use App\Enums\BranchRole;
use App\Enums\CheckInMethod;
use App\Livewire\Services\ServiceShow;
use App\Models\Tenant;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
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

    // Create a test service
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);

    // Create a test member
    $this->member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Create a test visitor
    $this->visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// VIEW ATTENDANCE TESTS
// ============================================

test('service show displays attendance records section', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee('Attendance Records');
});

test('service show displays no attendance records message when empty', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee('No attendance records for this service.');
});

test('service show displays existing attendance records', function (): void {
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
        'check_in_time' => '09:00',
        'check_in_method' => CheckInMethod::Manual,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->member->fullName())
        ->assertSee('09:00');
});

// ============================================
// ADD ATTENDANCE TESTS
// ============================================

test('admin can add attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->assertSet('showAddAttendanceModal', true)
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:30')
        ->set('attendanceCheckInMethod', 'manual')
        ->call('addAttendance')
        ->assertSet('showAddAttendanceModal', false)
        ->assertDispatched('attendance-added');

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('manager can add attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:30')
        ->call('addAttendance')
        ->assertDispatched('attendance-added');

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('staff can add attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:30')
        ->call('addAttendance')
        ->assertDispatched('attendance-added');

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('volunteer cannot add attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('openAddAttendanceModal')
        ->assertForbidden();
});

test('attendance can include check-out time', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->set('attendanceCheckOutTime', '11:30')
        ->call('addAttendance');

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect(substr($attendance->check_in_time, 0, 5))->toBe('09:00');
    expect(substr($attendance->check_out_time, 0, 5))->toBe('11:30');
});

test('attendance can include notes', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->set('attendanceNotes', 'First time visitor follow-up')
        ->call('addAttendance');

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect($attendance->notes)->toBe('First time visitor follow-up');
});

test('attendance check-in method can be qr', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->set('attendanceCheckInMethod', 'qr')
        ->call('addAttendance');

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Qr);
});

test('attendance check-in method can be kiosk', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->set('attendanceCheckInMethod', 'kiosk')
        ->call('addAttendance');

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Kiosk);
});

// ============================================
// DUPLICATE ATTENDANCE PREVENTION TESTS
// ============================================

test('cannot add duplicate attendance for same member and date', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $date = now()->format('Y-m-d');

    // Create existing attendance record
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => $date,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', $date)
        ->set('attendanceCheckInTime', '10:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceMemberId']);

    // Verify only one attendance record exists
    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('can add attendance for same member on different dates', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create existing attendance record for yesterday
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasNoErrors();

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(2);
});

// ============================================
// EDIT ATTENDANCE TESTS
// ============================================

test('admin can edit attendance record', function (): void {
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
        'date' => now()->format('Y-m-d'),
        'check_in_time' => '09:00',
        'check_in_method' => CheckInMethod::Manual,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('editAttendance', $attendance->id)
        ->assertSet('showAddAttendanceModal', true)
        ->assertSet('editingAttendanceId', $attendance->id)
        ->set('attendanceCheckInTime', '10:00')
        ->set('attendanceCheckOutTime', '12:00')
        ->call('saveAttendance')
        ->assertDispatched('attendance-updated');

    $attendance->refresh();
    expect(substr($attendance->check_in_time, 0, 5))->toBe('10:00');
    expect(substr($attendance->check_out_time, 0, 5))->toBe('12:00');
});

test('staff can edit attendance record', function (): void {
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
        'check_in_time' => '09:00',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('editAttendance', $attendance->id)
        ->set('attendanceCheckInTime', '09:30')
        ->call('saveAttendance')
        ->assertHasNoErrors();

    expect(substr($attendance->fresh()->check_in_time, 0, 5))->toBe('09:30');
});

test('volunteer cannot edit attendance record', function (): void {
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
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('editAttendance', $attendance->id)
        ->assertForbidden();
});

// ============================================
// DELETE ATTENDANCE TESTS
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

    $attendanceId = $attendance->id;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('deleteAttendance', $attendance->id)
        ->assertDispatched('attendance-deleted');

    expect(Attendance::find($attendanceId))->toBeNull();
});

test('staff can delete attendance record', function (): void {
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
    ]);

    $attendanceId = $attendance->id;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('deleteAttendance', $attendance->id)
        ->assertDispatched('attendance-deleted');

    expect(Attendance::find($attendanceId))->toBeNull();
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

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('deleteAttendance', $attendance->id)
        ->assertForbidden();
});

// ============================================
// VALIDATION TESTS
// ============================================

test('member is required when adding attendance', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId')
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceMemberId']);
});

test('date is required when adding attendance', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate')
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceDate']);
});

test('check-in time is required when adding attendance', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceCheckInTime']);
});

test('check-in time must be valid format', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', 'invalid')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceCheckInTime']);
});

test('cannot add attendance for member from different branch', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $otherBranch = Branch::factory()->create();
    $otherMember = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $otherMember->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceMemberId']);
});

test('cannot add attendance for inactive member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $inactiveMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'inactive',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $inactiveMember->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceMemberId']);
});

// ============================================
// MODAL BEHAVIOR TESTS
// ============================================

test('open add attendance modal sets default values', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->assertSet('showAddAttendanceModal', true)
        ->assertSet('attendanceDate', now()->format('Y-m-d'))
        ->assertSet('attendanceCheckInMethod', 'manual')
        ->assertSet('editingAttendanceId', null);
});

test('close add attendance modal resets form', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceNotes', 'Some notes')
        ->call('closeAddAttendanceModal')
        ->assertSet('showAddAttendanceModal', false)
        ->assertSet('attendanceMemberId', null)
        ->assertSet('attendanceNotes', null);
});

// ============================================
// COMPUTED PROPERTIES TESTS
// ============================================

test('attendanceRecords computed property returns records ordered by date', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create attendance records with different dates
    $oldAttendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'date' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $newAttendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => Member::factory()->create(['primary_branch_id' => $this->branch->id])->id,
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $records = $component->instance()->attendanceRecords;

    expect($records->count())->toBe(2);
    expect($records->first()->id)->toBe($newAttendance->id);
});

test('availableMembers computed property returns active branch members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create active and inactive members
    $activeMember1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'last_name' => 'Adams',
    ]);
    $activeMember2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'last_name' => 'Brown',
    ]);
    $inactiveMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'inactive',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $members = $component->instance()->availableMembers;

    // Should include $this->member (from beforeEach) + 2 active members
    expect($members->count())->toBe(3);
    expect($members->contains('id', $inactiveMember->id))->toBeFalse();
});

test('canManageAttendance returns true for staff and above', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->canManageAttendance)->toBeTrue();
});

test('canManageAttendance returns false for volunteer', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->canManageAttendance)->toBeFalse();
});

test('checkInMethods computed property returns all methods', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $methods = $component->instance()->checkInMethods;

    expect($methods)->toBe(CheckInMethod::cases());
    expect(count($methods))->toBe(3);
});

// ============================================
// VISITOR ATTENDANCE TESTS
// ============================================

test('admin can add visitor attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->assertSet('showAddAttendanceModal', true)
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $this->visitor->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:30')
        ->set('attendanceCheckInMethod', 'manual')
        ->call('addAttendance')
        ->assertSet('showAddAttendanceModal', false)
        ->assertDispatched('attendance-added');

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect($attendance)->not->toBeNull();
    expect($attendance->visitor_id)->toBe($this->visitor->id);
    expect($attendance->member_id)->toBeNull();
});

test('service show displays visitor attendance records', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'visitor_id' => $this->visitor->id,
        'member_id' => null,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => '10:00',
        'check_in_method' => CheckInMethod::Manual,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->visitor->fullName())
        ->assertSee('Visitor');
});

test('cannot add duplicate visitor attendance for same date', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $date = now()->format('Y-m-d');

    // Create existing visitor attendance record
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'visitor_id' => $this->visitor->id,
        'member_id' => null,
        'date' => $date,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $this->visitor->id)
        ->set('attendanceDate', $date)
        ->set('attendanceCheckInTime', '10:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceVisitorId']);

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(1);
});

test('can add visitor attendance for same visitor on different dates', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Create existing attendance record for yesterday
    Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'visitor_id' => $this->visitor->id,
        'member_id' => null,
        'date' => now()->subDay()->format('Y-m-d'),
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $this->visitor->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasNoErrors();

    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(2);
});

test('admin can edit visitor attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'visitor_id' => $this->visitor->id,
        'member_id' => null,
        'date' => now()->format('Y-m-d'),
        'check_in_time' => '09:00',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('editAttendance', $attendance->id)
        ->assertSet('attendanceType', 'visitor')
        ->assertSet('attendanceVisitorId', $this->visitor->id)
        ->set('attendanceCheckInTime', '10:00')
        ->call('saveAttendance')
        ->assertDispatched('attendance-updated');

    expect(substr($attendance->fresh()->check_in_time, 0, 5))->toBe('10:00');
});

test('visitor is required when attendance type is visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId')
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceVisitorId']);
});

test('cannot add attendance for visitor from different branch', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $otherBranch = Branch::factory()->create();
    $otherVisitor = Visitor::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $otherVisitor->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceVisitorId']);
});

test('cannot add attendance for converted visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $convertedVisitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'converted_member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $convertedVisitor->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasErrors(['attendanceVisitorId']);
});

test('attendance type defaults to member when opening modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->assertSet('attendanceType', 'member');
});

test('switching attendance type clears the other type id', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // Start with member, switch to visitor
    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('openAddAttendanceModal')
        ->set('attendanceMemberId', $this->member->id)
        ->set('attendanceType', 'visitor')
        ->set('attendanceVisitorId', $this->visitor->id)
        ->set('attendanceDate', now()->format('Y-m-d'))
        ->set('attendanceCheckInTime', '09:00')
        ->call('addAttendance')
        ->assertHasNoErrors();

    $attendance = Attendance::where('service_id', $this->service->id)->first();
    expect($attendance->visitor_id)->toBe($this->visitor->id);
    expect($attendance->member_id)->toBeNull();
});

test('availableVisitors computed property returns unconverted branch visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create additional visitors
    $visitor1 = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'last_name' => 'Adams',
    ]);
    $visitor2 = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'last_name' => 'Brown',
    ]);
    $convertedVisitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'converted_member_id' => $this->member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $visitors = $component->instance()->availableVisitors;

    // Should include $this->visitor (from beforeEach) + 2 new visitors, but not converted
    expect($visitors->count())->toBe(3);
    expect($visitors->contains('id', $convertedVisitor->id))->toBeFalse();
});

test('can delete visitor attendance record', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $attendance = Attendance::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'visitor_id' => $this->visitor->id,
        'member_id' => null,
    ]);

    $attendanceId = $attendance->id;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->call('deleteAttendance', $attendance->id)
        ->assertDispatched('attendance-deleted');

    expect(Attendance::find($attendanceId))->toBeNull();
});
