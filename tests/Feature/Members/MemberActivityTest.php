<?php

use App\Enums\ActivityEvent;
use App\Enums\BranchRole;
use App\Enums\MembershipStatus;
use App\Livewire\Members\MemberActivityLog;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
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
// OBSERVER TESTS - Activity Logged on Events
// ============================================

test('activity is logged when member is created', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect(MemberActivity::count())->toBe(1);

    $activity = MemberActivity::first();
    expect($activity->event)->toBe(ActivityEvent::Created)
        ->and($activity->member_id)->toBe($member->id)
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->new_values)->toHaveKey('first_name')
        ->and($activity->new_values['first_name'])->toBe('John');
});

test('activity is logged when member is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'status' => MembershipStatus::Active,
    ]);

    // Clear the creation activity
    MemberActivity::query()->delete();

    $member->update(['first_name' => 'Jane', 'status' => MembershipStatus::Inactive]);

    expect(MemberActivity::count())->toBe(1);

    $activity = MemberActivity::first();
    expect($activity->event)->toBe(ActivityEvent::Updated)
        ->and($activity->changed_fields)->toContain('first_name')
        ->and($activity->changed_fields)->toContain('status')
        ->and($activity->old_values['first_name'])->toBe('John')
        ->and($activity->new_values['first_name'])->toBe('Jane');
});

test('activity is not logged when only timestamps change', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Clear the creation activity
    MemberActivity::query()->delete();

    // Touch the model to update only timestamps
    $member->touch();

    expect(MemberActivity::count())->toBe(0);
});

test('activity is logged when member is soft deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Clear the creation activity
    MemberActivity::query()->delete();

    $member->delete();

    expect(MemberActivity::count())->toBe(1);

    $activity = MemberActivity::first();
    expect($activity->event)->toBe(ActivityEvent::Deleted)
        ->and($activity->member_id)->toBe($member->id);
});

test('activity is logged when member is restored', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);
    $member->delete();

    // Clear previous activities
    MemberActivity::query()->delete();

    $member->restore();

    expect(MemberActivity::count())->toBe(1);

    $activity = MemberActivity::first();
    expect($activity->event)->toBe(ActivityEvent::Restored)
        ->and($activity->member_id)->toBe($member->id);
});

test('activity captures authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $activity = MemberActivity::first();
    expect($activity->user_id)->toBe($user->id);
});

test('activity captures IP address', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $activity = MemberActivity::first();
    expect($activity->ip_address)->not->toBeNull();
});

test('enum values are serialized correctly in activity log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);

    // Clear creation activity
    MemberActivity::query()->delete();

    $member->update(['status' => MembershipStatus::Inactive]);

    $activity = MemberActivity::first();
    expect($activity->old_values['status'])->toBe('active')
        ->and($activity->new_values['status'])->toBe('inactive');
});

// ============================================
// COMPONENT TESTS - Activity Display
// ============================================

test('activity log component displays on member show page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/members/{$member->id}")
        ->assertOk()
        ->assertSeeLivewire(MemberActivityLog::class);
});

test('activity log shows empty state when no activities', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Clear activities
    MemberActivity::query()->delete();

    Livewire::actingAs($user)
        ->test(MemberActivityLog::class, ['member' => $member])
        ->assertSee('No activity recorded yet.');
});

test('activity log displays activities with correct information', function () {
    $user = User::factory()->create(['name' => 'Test User']);
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
    ]);

    Livewire::actingAs($user)
        ->test(MemberActivityLog::class, ['member' => $member])
        ->assertSee('Test User created this member');
});

test('activity log shows load more button when there are more activities', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Create 15 activities (more than the default limit of 10)
    MemberActivity::factory()->count(14)->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MemberActivityLog::class, ['member' => $member])
        ->assertSee('Load more');
});

test('load more increases the activity limit', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Create 15 activities
    MemberActivity::factory()->count(14)->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MemberActivityLog::class, ['member' => $member])
        ->assertSet('limit', 10)
        ->call('loadMore')
        ->assertSet('limit', 20);
});

// ============================================
// MODEL TESTS
// ============================================

test('member has activities relationship', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    expect($member->activities)->toHaveCount(1);
    expect($member->activities->first())->toBeInstanceOf(MemberActivity::class);
});

test('activity formatted description shows correct message for each event type', function () {
    $user = User::factory()->create(['name' => 'John Admin']);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    // Clear activities first
    MemberActivity::query()->delete();

    $createdActivity = MemberActivity::factory()->created()->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);
    expect($createdActivity->formatted_description)->toBe('John Admin created this member');

    $updatedActivity = MemberActivity::factory()->updated()->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);
    expect($updatedActivity->formatted_description)->toContain('John Admin updated');

    $deletedActivity = MemberActivity::factory()->deleted()->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);
    expect($deletedActivity->formatted_description)->toBe('John Admin deleted this member');

    $restoredActivity = MemberActivity::factory()->restored()->create([
        'member_id' => $member->id,
        'user_id' => $user->id,
    ]);
    expect($restoredActivity->formatted_description)->toBe('John Admin restored this member');
});
