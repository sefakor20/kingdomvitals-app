<?php

use App\Enums\ActivityEvent;
use App\Enums\BranchRole;
use App\Enums\SubjectType;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Models\Tenant\ActivityLog;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admins can view activity logs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('managers can view activity logs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);
    $this->actingAs($user);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->assertStatus(200);
});

test('regular staff cannot view activity logs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);
    $this->actingAs($user);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->assertForbidden();
});

// ============================================
// DISPLAY TESTS
// ============================================

test('component displays activity logs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 3);
});

test('component shows empty state when no logs', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->assertSee('No activity found');
});

// ============================================
// FILTER TESTS
// ============================================

test('component can filter by subject type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject_type' => SubjectType::Member,
    ]);
    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject_type' => SubjectType::Donation,
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->set('subjectType', SubjectType::Member->value)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('component can filter by event type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->created()->create([
        'branch_id' => $this->branch->id,
    ]);
    ActivityLog::factory()->updated()->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->set('event', ActivityEvent::Created->value)
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('component can search by description', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject_name' => 'John Doe',
    ]);
    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'subject_name' => 'Jane Smith',
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->set('search', 'John')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('component can filter by date range', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now()->subDays(5),
    ]);
    ActivityLog::factory()->create([
        'branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subDays(1)->format('Y-m-d'))
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 1);
});

test('component can clear all filters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->set('search', 'test')
        ->set('subjectType', SubjectType::Member->value)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('subjectType', '')
        ->assertViewHas('logs', fn ($logs) => $logs->count() === 2);
});

// ============================================
// EXPORT TESTS
// ============================================

test('component can export activity logs to csv', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
    $this->actingAs($user);

    ActivityLog::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    $response = Livewire::test(ActivityLogIndex::class, ['branch' => $this->branch])
        ->call('exportCsv');

    expect($response->effects['download'])->not->toBeNull();
});
