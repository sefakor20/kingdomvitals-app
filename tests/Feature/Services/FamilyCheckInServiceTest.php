<?php

use App\Enums\CheckInMethod;
use App\Enums\HouseholdRole;
use App\Models\Tenant;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\FamilyCheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $this->familyService = app(FamilyCheckInService::class);
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// FAMILY CHECK-IN TESTS
// ============================================

test('can check in multiple family members at once', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Head,
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Spouse,
    ]);

    $member3 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Child,
    ]);

    $checkedIn = $this->familyService->checkInFamily(
        $household,
        $this->service,
        $this->branch,
        [$member1->id, $member2->id, $member3->id]
    );

    expect($checkedIn->count())->toBe(3);
    expect(Attendance::where('service_id', $this->service->id)->count())->toBe(3);
});

test('skips members not in household', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $memberNotInHousehold = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => null,
    ]);

    $checkedIn = $this->familyService->checkInFamily(
        $household,
        $this->service,
        $this->branch,
        [$member1->id, $memberNotInHousehold->id]
    );

    expect($checkedIn->count())->toBe(1);
    expect(Attendance::where('member_id', $member1->id)->exists())->toBeTrue();
    expect(Attendance::where('member_id', $memberNotInHousehold->id)->exists())->toBeFalse();
});

test('skips already checked in members', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
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

    $checkedIn = $this->familyService->checkInFamily(
        $household,
        $this->service,
        $this->branch,
        [$member1->id, $member2->id]
    );

    expect($checkedIn->count())->toBe(1);
    expect(Attendance::where('member_id', $member2->id)->exists())->toBeTrue();
});

test('uses specified check-in method', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $this->familyService->checkInFamily(
        $household,
        $this->service,
        $this->branch,
        [$member->id],
        CheckInMethod::Mobile
    );

    $attendance = Attendance::where('member_id', $member->id)->first();
    expect($attendance->check_in_method)->toBe(CheckInMethod::Mobile);
});

// ============================================
// CHILDREN CHECK-IN WITH SECURITY TESTS
// ============================================

test('can check in child with security code', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $guardian = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Head,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Child,
        'date_of_birth' => now()->subYears(5),
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        $guardian,
        $this->service,
        $this->branch
    );

    expect($security)->toBeInstanceOf(ChildrenCheckinSecurity::class);
    expect($security->child_member_id)->toBe($child->id);
    expect($security->guardian_member_id)->toBe($guardian->id);
    expect($security->security_code)->toHaveLength(6);
    expect($security->is_checked_out)->toBeFalse();

    expect(Attendance::where('member_id', $child->id)->exists())->toBeTrue();
});

test('can check in child without guardian', function (): void {
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        null,
        $this->service,
        $this->branch
    );

    expect($security->guardian_member_id)->toBeNull();
    expect($security->security_code)->toHaveLength(6);
});

// ============================================
// SECURITY CODE VERIFICATION TESTS
// ============================================

test('can verify valid security code', function (): void {
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        null,
        $this->service,
        $this->branch
    );

    $verified = $this->familyService->verifySecurityCode(
        $security->security_code,
        $this->service,
        now()->toDateString()
    );

    expect($verified)->not->toBeNull();
    expect($verified->id)->toBe($security->id);
});

test('returns null for invalid security code', function (): void {
    $verified = $this->familyService->verifySecurityCode(
        '000000',
        $this->service,
        now()->toDateString()
    );

    expect($verified)->toBeNull();
});

test('returns null for already checked out security code', function (): void {
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        null,
        $this->service,
        $this->branch
    );

    // Mark as checked out
    $security->checkOut();

    $verified = $this->familyService->verifySecurityCode(
        $security->security_code,
        $this->service,
        now()->toDateString()
    );

    expect($verified)->toBeNull();
});

test('returns null for security code from different service', function (): void {
    $otherService = Service::factory()->create(['branch_id' => $this->branch->id]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        null,
        $this->service,
        $this->branch
    );

    $verified = $this->familyService->verifySecurityCode(
        $security->security_code,
        $otherService,
        now()->toDateString()
    );

    expect($verified)->toBeNull();
});

// ============================================
// GET HOUSEHOLD MEMBERS TESTS
// ============================================

test('can get household members in correct order', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Child,
        'first_name' => 'Zack',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Spouse,
        'first_name' => 'Jane',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'household_role' => HouseholdRole::Head,
        'first_name' => 'John',
    ]);

    $members = $this->familyService->getHouseholdMembers($household);

    expect($members->count())->toBe(3);
    // Order should be: Head, Spouse, Child
    expect($members->first()->household_role)->toBe(HouseholdRole::Head);
    expect($members->get(1)->household_role)->toBe(HouseholdRole::Spouse);
    expect($members->last()->household_role)->toBe(HouseholdRole::Child);
});

test('excludes inactive members from household list', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'active',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
        'status' => 'inactive',
    ]);

    $members = $this->familyService->getHouseholdMembers($household);

    expect($members->count())->toBe(1);
});

// ============================================
// GET ALREADY CHECKED IN MEMBERS TESTS
// ============================================

test('can get already checked in members', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    // Check in member1
    Attendance::create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'date' => now()->toDateString(),
        'check_in_time' => now()->toTimeString(),
        'check_in_method' => CheckInMethod::Kiosk,
    ]);

    $checkedIn = $this->familyService->getAlreadyCheckedInMembers(
        $household,
        $this->service,
        now()->toDateString()
    );

    expect($checkedIn->count())->toBe(1);
    expect($checkedIn->first()->id)->toBe($member1->id);
});

test('returns empty collection when no members checked in', function (): void {
    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'household_id' => $household->id,
    ]);

    $checkedIn = $this->familyService->getAlreadyCheckedInMembers(
        $household,
        $this->service,
        now()->toDateString()
    );

    expect($checkedIn->count())->toBe(0);
});

// ============================================
// SECURITY CODE GENERATION TESTS
// ============================================

test('security code is always 6 digits', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $code = ChildrenCheckinSecurity::generateSecurityCode();
        expect($code)->toHaveLength(6);
        expect(is_numeric($code))->toBeTrue();
    }
});

test('can check out child with security record', function (): void {
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $guardian = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $security = $this->familyService->checkInChildWithSecurity(
        $child,
        $guardian,
        $this->service,
        $this->branch
    );

    expect($security->is_checked_out)->toBeFalse();

    $security->checkOut($guardian);

    expect($security->fresh()->is_checked_out)->toBeTrue();
    expect($security->fresh()->checked_out_at)->not->toBeNull();
    expect($security->fresh()->checked_out_by_id)->toBe($guardian->id);
});
