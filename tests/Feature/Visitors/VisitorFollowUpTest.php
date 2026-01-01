<?php

use App\Enums\BranchRole;
use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Enums\VisitorStatus;
use App\Livewire\Visitors\VisitorShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
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

    // Create a test visitor
    $this->visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admin can add follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->assertSet('showAddFollowUpModal', true)
        ->set('followUpType', 'call')
        ->set('followUpOutcome', 'successful')
        ->set('followUpNotes', 'Spoke with visitor, very interested.')
        ->call('addFollowUp')
        ->assertSet('showAddFollowUpModal', false)
        ->assertDispatched('follow-up-added');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'type' => 'call',
        'outcome' => 'successful',
        'notes' => 'Spoke with visitor, very interested.',
    ]);
});

test('manager can add follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->assertSet('showAddFollowUpModal', true)
        ->set('followUpType', 'sms')
        ->set('followUpOutcome', 'no_answer')
        ->call('addFollowUp')
        ->assertDispatched('follow-up-added');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'type' => 'sms',
        'outcome' => 'no_answer',
    ]);
});

test('staff can add follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->assertSet('showAddFollowUpModal', true)
        ->set('followUpType', 'email')
        ->set('followUpOutcome', 'successful')
        ->call('addFollowUp')
        ->assertDispatched('follow-up-added');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'type' => 'email',
    ]);
});

test('volunteer cannot add follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->assertForbidden();
});

// ============================================
// SCHEDULE FOLLOW-UP TESTS
// ============================================

test('admin can schedule follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $scheduledAt = now()->addDays(3)->format('Y-m-d\TH:i');

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openScheduleFollowUpModal')
        ->assertSet('showScheduleFollowUpModal', true)
        ->set('followUpType', 'visit')
        ->set('followUpScheduledAt', $scheduledAt)
        ->set('followUpNotes', 'Home visit scheduled')
        ->call('scheduleFollowUp')
        ->assertSet('showScheduleFollowUpModal', false)
        ->assertDispatched('follow-up-scheduled');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'type' => 'visit',
        'outcome' => 'pending',
        'is_scheduled' => true,
        'notes' => 'Home visit scheduled',
    ]);
});

test('scheduled follow-up updates visitor next_follow_up_at', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $scheduledAt = now()->addDays(2);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openScheduleFollowUpModal')
        ->set('followUpType', 'call')
        ->set('followUpScheduledAt', $scheduledAt->format('Y-m-d\TH:i'))
        ->call('scheduleFollowUp');

    $this->visitor->refresh();
    expect($this->visitor->next_follow_up_at)->not->toBeNull();
    expect($this->visitor->next_follow_up_at->format('Y-m-d'))->toBe($scheduledAt->format('Y-m-d'));
});

test('cannot schedule follow-up in the past', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $pastDate = now()->subDay()->format('Y-m-d\TH:i');

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openScheduleFollowUpModal')
        ->set('followUpType', 'call')
        ->set('followUpScheduledAt', $pastDate)
        ->call('scheduleFollowUp')
        ->assertHasErrors('followUpScheduledAt');
});

// ============================================
// COMPLETE FOLLOW-UP TESTS
// ============================================

test('admin can complete scheduled follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'type' => FollowUpType::Call,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('startCompleteFollowUp', $followUp)
        ->assertSet('completingFollowUp.id', $followUp->id)
        ->set('followUpOutcome', 'successful')
        ->set('followUpNotes', 'Call completed successfully')
        ->call('completeFollowUp')
        ->assertSet('completingFollowUp', null)
        ->assertDispatched('follow-up-completed');

    $followUp->refresh();
    expect($followUp->outcome)->toBe(FollowUpOutcome::Successful);
    expect($followUp->completed_at)->not->toBeNull();
    expect($followUp->notes)->toBe('Call completed successfully');
});

test('completing follow-up updates visitor stats', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('startCompleteFollowUp', $followUp)
        ->set('followUpOutcome', 'successful')
        ->call('completeFollowUp');

    $this->visitor->refresh();
    expect($this->visitor->follow_up_count)->toBe(1);
    expect($this->visitor->last_follow_up_at)->not->toBeNull();
});

test('completing follow-up on new visitor updates status to followed_up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Ensure visitor is "new"
    $this->visitor->update(['status' => VisitorStatus::New]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('startCompleteFollowUp', $followUp)
        ->set('followUpOutcome', 'successful')
        ->call('completeFollowUp');

    $this->visitor->refresh();
    expect($this->visitor->status)->toBe(VisitorStatus::FollowedUp);
});

// ============================================
// CANCEL FOLLOW-UP TESTS
// ============================================

test('admin can cancel scheduled follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('cancelFollowUp', $followUp)
        ->assertDispatched('follow-up-cancelled');

    $this->assertDatabaseMissing('visitor_follow_ups', ['id' => $followUp->id]);
});

test('manager can cancel scheduled follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('cancelFollowUp', $followUp)
        ->assertDispatched('follow-up-cancelled');

    $this->assertDatabaseMissing('visitor_follow_ups', ['id' => $followUp->id]);
});

test('staff cannot cancel scheduled follow-up', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $followUp = VisitorFollowUp::factory()
        ->scheduled()
        ->create([
            'visitor_id' => $this->visitor->id,
            'scheduled_at' => now()->addDay(),
        ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('cancelFollowUp', $followUp)
        ->assertForbidden();
});

// ============================================
// ADD FOLLOW-UP WITH MEMBER ASSIGNMENT TESTS
// ============================================

test('follow-up can be assigned to a member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->set('followUpType', 'call')
        ->set('followUpOutcome', 'successful')
        ->set('followUpPerformedBy', $member->id)
        ->call('addFollowUp');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'performed_by' => $member->id,
    ]);
});

// ============================================
// FOLLOW-UP TYPES TESTS
// ============================================

test('all follow-up types can be used', function (string $type) {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->set('followUpType', $type)
        ->set('followUpOutcome', 'successful')
        ->call('addFollowUp')
        ->assertDispatched('follow-up-added');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'type' => $type,
    ]);
})->with([
    'call',
    'sms',
    'email',
    'visit',
    'whatsapp',
    'other',
]);

// ============================================
// FOLLOW-UP OUTCOMES TESTS
// ============================================

test('all follow-up outcomes can be recorded', function (string $outcome) {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->call('openAddFollowUpModal')
        ->set('followUpType', 'call')
        ->set('followUpOutcome', $outcome)
        ->call('addFollowUp')
        ->assertDispatched('follow-up-added');

    $this->assertDatabaseHas('visitor_follow_ups', [
        'visitor_id' => $this->visitor->id,
        'outcome' => $outcome,
    ]);
})->with([
    'successful',
    'no_answer',
    'voicemail',
    'callback',
    'not_interested',
    'wrong_number',
    'rescheduled',
]);

// ============================================
// FOLLOW-UP HISTORY DISPLAY TESTS
// ============================================

test('follow-up history is displayed on visitor show page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $followUp = VisitorFollowUp::factory()->completed()->create([
        'visitor_id' => $this->visitor->id,
        'type' => FollowUpType::Call,
        'outcome' => FollowUpOutcome::Successful,
        'notes' => 'Great conversation with the visitor',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->assertSee('Call')
        ->assertSee('Successful')
        ->assertSee('Great conversation with the visitor');
});

test('pending follow-ups are displayed separately', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $pendingFollowUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'type' => FollowUpType::Visit,
        'scheduled_at' => now()->addDays(2),
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $this->visitor])
        ->assertSee('Scheduled')
        ->assertSee('Visit');
});
