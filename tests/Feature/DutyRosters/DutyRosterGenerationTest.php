<?php

use App\Enums\BranchRole;
use App\Enums\DutyRosterRoleType;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\DutyRosterPool;
use App\Models\Tenant\DutyRosterPoolMember;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberUnavailability;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use App\Services\DutyRosterGenerationService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['duty_roster', 'members', 'clusters', 'services'],
    ]);
    \Illuminate\Support\Facades\Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// POOL MODEL TESTS
// ============================================

test('can create personnel pool', function (): void {
    $user = User::factory()->create();

    $pool = DutyRosterPool::create([
        'branch_id' => $this->branch->id,
        'role_type' => DutyRosterRoleType::Preacher,
        'name' => 'Sunday Preachers',
        'description' => 'Pool of preachers for Sunday services',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    expect($pool)->not->toBeNull();
    expect($pool->name)->toBe('Sunday Preachers');
    expect($pool->role_type)->toBe(DutyRosterRoleType::Preacher);

    $this->assertDatabaseHas('duty_roster_pools', [
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Preachers',
        'role_type' => 'preacher',
    ]);
});

test('can add members to pool', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $pool->members()->attach($member->id, [
        'is_active' => true,
        'assignment_count' => 0,
        'sort_order' => 0,
    ]);

    expect($pool->members)->toHaveCount(1);
    expect($pool->members->first()->id)->toBe($member->id);

    $this->assertDatabaseHas('duty_roster_pool_member', [
        'duty_roster_pool_id' => $pool->id,
        'member_id' => $member->id,
    ]);
});

// ============================================
// AVAILABILITY MODEL TESTS
// ============================================

test('can mark member as unavailable', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $unavailability = MemberUnavailability::create([
        'member_id' => $member->id,
        'branch_id' => $this->branch->id,
        'unavailable_date' => now()->addWeek(),
        'reason' => 'Traveling',
        'created_by' => $user->id,
    ]);

    expect($unavailability)->not->toBeNull();

    $this->assertDatabaseHas('member_unavailabilities', [
        'member_id' => $member->id,
        'branch_id' => $this->branch->id,
        'reason' => 'Traveling',
    ]);
});

test('can check if member is unavailable on date', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $targetDate = now()->addWeek();

    MemberUnavailability::create([
        'member_id' => $member->id,
        'branch_id' => $this->branch->id,
        'unavailable_date' => $targetDate,
        'reason' => 'Traveling',
    ]);

    expect(MemberUnavailability::isMemberUnavailable($member->id, $this->branch->id, $targetDate))->toBeTrue();
    expect(MemberUnavailability::isMemberUnavailable($member->id, $this->branch->id, $targetDate->copy()->addDay()))->toBeFalse();
});

// ============================================
// GENERATION SERVICE TESTS
// ============================================

test('service generates dates for recurring service', function (): void {
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => 0, // Sunday
    ]);

    $generationService = app(DutyRosterGenerationService::class);

    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();

    $dates = $generationService->generateDatesForService($service, $startDate, $endDate);

    // Should have 4-5 Sundays in a month
    expect($dates->count())->toBeGreaterThanOrEqual(4);
    expect($dates->count())->toBeLessThanOrEqual(5);

    // All dates should be Sundays
    foreach ($dates as $date) {
        expect($date->dayOfWeek)->toBe(0);
    }
});

test('service generates dates for multiple days of week', function (): void {
    $generationService = app(DutyRosterGenerationService::class);

    $startDate = now()->startOfMonth();
    $endDate = now()->endOfMonth();

    // Generate for Sundays (0) and Wednesdays (3)
    $dates = $generationService->generateDatesForDays([0, 3], $startDate, $endDate);

    // Should have 8-10 dates (4-5 Sundays + 4-5 Wednesdays)
    expect($dates->count())->toBeGreaterThanOrEqual(8);
    expect($dates->count())->toBeLessThanOrEqual(10);

    // All dates should be sorted chronologically
    $previousDate = null;
    foreach ($dates as $date) {
        if ($previousDate) {
            expect($date->gte($previousDate))->toBeTrue();
        }
        $previousDate = $date;
    }
});

test('service implements round-robin assignment', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    foreach ($members as $index => $member) {
        $pool->members()->attach($member->id, [
            'is_active' => true,
            'assignment_count' => 0,
            'sort_order' => $index,
        ]);
    }

    $generationService = app(DutyRosterGenerationService::class);

    // Get members in sequence - should rotate through all three
    $assigned = [];
    $dates = [now()->addDays(1), now()->addDays(2), now()->addDays(3)];

    foreach ($dates as $date) {
        $member = $generationService->getNextAvailableMember($pool->fresh(), $date);
        if ($member) {
            $assigned[] = $member->id;
            // Update assignment count directly
            DutyRosterPoolMember::where('duty_roster_pool_id', $pool->id)
                ->where('member_id', $member->id)
                ->increment('assignment_count');
            DutyRosterPoolMember::where('duty_roster_pool_id', $pool->id)
                ->where('member_id', $member->id)
                ->update(['last_assigned_date' => $date]);
        }
    }

    // All three members should be assigned exactly once
    expect(array_unique($assigned))->toHaveCount(3);
});

test('service skips unavailable members during assignment', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $members = Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    foreach ($members as $index => $member) {
        $pool->members()->attach($member->id, [
            'is_active' => true,
            'assignment_count' => 0,
            'sort_order' => $index,
        ]);
    }

    $targetDate = now()->addWeek();

    // Mark first member as unavailable on target date
    MemberUnavailability::create([
        'member_id' => $members[0]->id,
        'branch_id' => $this->branch->id,
        'unavailable_date' => $targetDate,
        'reason' => 'Traveling',
    ]);

    $generationService = app(DutyRosterGenerationService::class);
    $assigned = $generationService->getNextAvailableMember($pool, $targetDate);

    // Should skip first member and assign second
    expect($assigned->id)->toBe($members[1]->id);
});

test('service skips inactive pool members', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $members = Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // First member is inactive
    $pool->members()->attach($members[0]->id, [
        'is_active' => false,
        'assignment_count' => 0,
        'sort_order' => 0,
    ]);

    // Second member is active
    $pool->members()->attach($members[1]->id, [
        'is_active' => true,
        'assignment_count' => 0,
        'sort_order' => 1,
    ]);

    $generationService = app(DutyRosterGenerationService::class);
    $assigned = $generationService->getNextAvailableMember($pool, now()->addDay());

    // Should skip inactive first member and assign second
    expect($assigned->id)->toBe($members[1]->id);
});

test('service returns null when no members available', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    // Pool has no members
    $generationService = app(DutyRosterGenerationService::class);
    $assigned = $generationService->getNextAvailableMember($pool, now()->addDay());

    expect($assigned)->toBeNull();
});

test('service generates rosters with pool assignments', function (): void {
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => 0, // Sunday
    ]);

    $preacherPool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $preacher = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $preacherPool->members()->attach($preacher->id, [
        'is_active' => true,
        'assignment_count' => 0,
        'sort_order' => 0,
    ]);

    $generationService = app(DutyRosterGenerationService::class);

    $config = [
        'service_id' => $service->id,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->addWeeks(2)->format('Y-m-d'),
        'preacher_pool_id' => $preacherPool->id,
    ];

    $rosters = $generationService->generateRosters($this->branch, $config);

    expect($rosters->count())->toBeGreaterThanOrEqual(2);

    // All rosters should have the preacher assigned
    foreach ($rosters as $roster) {
        expect($roster->preacher_id)->toBe($preacher->id);
    }
});

test('service skips existing roster dates when generating', function (): void {
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => 0, // Sunday
    ]);

    // Create an existing roster for next Sunday
    $nextSunday = now()->next('Sunday');
    DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'service_date' => $nextSunday,
    ]);

    $generationService = app(DutyRosterGenerationService::class);

    $config = [
        'service_id' => $service->id,
        'start_date' => $nextSunday->format('Y-m-d'),
        'end_date' => $nextSunday->copy()->addWeek()->format('Y-m-d'),
        'skip_existing' => true,
    ];

    $rosters = $generationService->generateRosters($this->branch, $config);

    // Should only generate 1 roster (for the second Sunday), skipping the first
    expect($rosters->count())->toBe(1);
    expect($rosters->first()->service_date->format('Y-m-d'))->toBe($nextSunday->copy()->addWeek()->format('Y-m-d'));
});

test('service preview shows conflicts for existing rosters', function (): void {
    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => 0, // Sunday
    ]);

    // Create an existing roster for next Sunday
    $nextSunday = now()->next('Sunday');
    DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'service_date' => $nextSunday,
    ]);

    $generationService = app(DutyRosterGenerationService::class);

    $config = [
        'service_id' => $service->id,
        'start_date' => $nextSunday->format('Y-m-d'),
        'end_date' => $nextSunday->copy()->addWeek()->format('Y-m-d'),
    ];

    $preview = $generationService->previewGeneration($this->branch, $config);

    // First entry should have a conflict
    expect($preview[0]['conflicts'])->toContain('A roster already exists for this date');
});

test('service can reset pool rotation counters', function (): void {
    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $pool->members()->attach($member->id, [
        'is_active' => true,
        'assignment_count' => 5,
        'sort_order' => 0,
        'last_assigned_date' => now(),
    ]);

    $generationService = app(DutyRosterGenerationService::class);
    $generationService->resetPoolRotation($pool);

    $pivotData = $pool->fresh()->members->first()->pivot;
    expect($pivotData->assignment_count)->toBe(0);
    expect($pivotData->last_assigned_date)->toBeNull();
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admin can manage pools', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('update', $pool))->toBeTrue();
    expect($user->can('delete', $pool))->toBeTrue();
});

test('volunteer cannot manage pools', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $pool = DutyRosterPool::factory()->preacher()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('create', [DutyRosterPool::class, $this->branch]))->toBeFalse();
    expect($user->can('update', $pool))->toBeFalse();
    expect($user->can('delete', $pool))->toBeFalse();
});

test('admin can generate duty rosters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $roster = DutyRoster::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    expect($user->can('generate', [DutyRoster::class, $this->branch]))->toBeTrue();
});

test('volunteer cannot generate duty rosters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    expect($user->can('generate', [DutyRoster::class, $this->branch]))->toBeFalse();
});
