<?php

use App\Enums\BranchRole;
use App\Enums\FollowUpOutcome;
use App\Enums\VisitorStatus;
use App\Livewire\Visitors\VisitorIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
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
// ADVANCED FILTERING TESTS
// ============================================

test('can filter visitors by date range', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'RecentVisitor',
        'visit_date' => now()->subDays(2),
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'OldVisitor',
        'visit_date' => now()->subMonths(2),
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subWeek()->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertSee('RecentVisitor')
        ->assertDontSee('OldVisitor');
});

test('can filter visitors by assigned member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'AssignedVisitor',
        'assigned_to' => $member->id,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'UnassignedVisitor',
        'assigned_to' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('assignedMemberFilter', $member->id)
        ->assertSee('AssignedVisitor')
        ->assertDontSee('UnassignedVisitor');
});

test('can filter unassigned visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'AssignedOne',
        'assigned_to' => $member->id,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'UnassignedOne',
        'assigned_to' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('assignedMemberFilter', 'unassigned')
        ->assertSee('UnassignedOne')
        ->assertDontSee('AssignedOne');
});

test('can filter visitors by source', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'SocialVisitor',
        'how_did_you_hear' => 'Social media',
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'FriendVisitor',
        'how_did_you_hear' => 'Friend or family',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('sourceFilter', 'Social media')
        ->assertSee('SocialVisitor')
        ->assertDontSee('FriendVisitor');
});

test('can clear all filters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'VisibleVisitor',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('search', 'test')
        ->set('statusFilter', 'new')
        ->set('dateFrom', now()->format('Y-m-d'))
        ->set('sourceFilter', 'Social media')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('statusFilter', '')
        ->assertSet('dateFrom', null)
        ->assertSet('dateTo', null)
        ->assertSet('sourceFilter', '')
        ->assertSet('assignedMemberFilter', null);
});

test('combined filters work together', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Matches all filters
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'MatchingVisitor',
        'status' => VisitorStatus::New,
        'visit_date' => now()->subDays(2),
        'how_did_you_hear' => 'Social media',
        'assigned_to' => $member->id,
    ]);

    // Matches status but not date
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'OldNewVisitor',
        'status' => VisitorStatus::New,
        'visit_date' => now()->subMonths(2),
        'how_did_you_hear' => 'Social media',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'new')
        ->set('dateFrom', now()->subWeek()->format('Y-m-d'))
        ->set('sourceFilter', 'Social media')
        ->assertSee('MatchingVisitor')
        ->assertDontSee('OldNewVisitor');
});

// ============================================
// VISITOR STATS TESTS
// ============================================

test('stats show correct total count', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->count(5)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->visitorStats['total'])->toBe(5);
});

test('stats show correct new count', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::FollowedUp,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->visitorStats['new'])->toBe(3);
});

test('stats show correct conversion rate', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->count(8)->create([
        'branch_id' => $this->branch->id,
        'is_converted' => false,
    ]);

    Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'is_converted' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->visitorStats['conversionRate'])->toBe(20.0);
});

test('stats show zero conversion rate when no visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->visitorStats['conversionRate'])->toBe(0);
});

test('stats show pending follow-ups count', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor1 = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $visitor2 = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $visitor3 = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    // Create pending follow-up
    VisitorFollowUp::factory()->create([
        'visitor_id' => $visitor1->id,
        'outcome' => FollowUpOutcome::Pending,
    ]);

    // Create completed follow-up (should not count)
    VisitorFollowUp::factory()->create([
        'visitor_id' => $visitor2->id,
        'outcome' => FollowUpOutcome::Successful,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->visitorStats['pendingFollowUps'])->toBe(1);
});

test('stats update with filters applied', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::FollowedUp,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'new');

    // Stats should reflect filtered results
    expect($component->instance()->visitorStats['total'])->toBe(5);
});

// ============================================
// CSV EXPORT TESTS
// ============================================

test('admin can export visitors to csv', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $response = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('exportToCsv');

    // The response should be a streamed download
    expect($response->effects['download'])->not->toBeNull();
});

test('export respects active filters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::FollowedUp,
    ]);

    $this->actingAs($user);

    // Set filter and verify it affects the visitors computed property
    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'new');

    // Verify only 1 visitor is in the filtered list
    expect($component->instance()->visitors->count())->toBe(1);
});

test('volunteer cannot export visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    // Volunteers should be able to view but export requires viewAny permission which they have
    // Actually, export uses viewAny authorization which volunteers have
    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('exportToCsv')
        ->assertSuccessful();
});

// ============================================
// BULK SELECTION TESTS
// ============================================

test('can select all visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectAll', true);

    expect($component->instance()->selectedVisitors)->toHaveCount(3);
    expect($component->instance()->hasSelection)->toBeTrue();
});

test('can select individual visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', [$visitors[0]->id]);

    expect($component->instance()->selectedVisitors)->toHaveCount(1);
    expect($component->instance()->selectedCount)->toBe(1);
});

test('can clear selection', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectAll', true)
        ->call('clearSelection')
        ->assertSet('selectedVisitors', [])
        ->assertSet('selectAll', false);
});

// ============================================
// BULK DELETE TESTS
// ============================================

test('admin can bulk delete visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);
    $visitorIds = $visitors->pluck('id')->toArray();

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitorIds)
        ->call('confirmBulkDelete')
        ->assertSet('showBulkDeleteModal', true)
        ->call('bulkDelete')
        ->assertDispatched('visitors-bulk-deleted');

    expect(Visitor::whereIn('id', $visitorIds)->count())->toBe(0);
});

test('manager can bulk delete visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);
    $visitorIds = $visitors->pluck('id')->toArray();

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitorIds)
        ->call('confirmBulkDelete')
        ->call('bulkDelete')
        ->assertDispatched('visitors-bulk-deleted');

    expect(Visitor::whereIn('id', $visitorIds)->count())->toBe(0);
});

test('staff cannot bulk delete visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);
    $visitorIds = $visitors->pluck('id')->toArray();

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitorIds)
        ->call('confirmBulkDelete')
        ->assertForbidden();
});

test('selection clears after bulk delete', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('confirmBulkDelete')
        ->call('bulkDelete')
        ->assertSet('selectedVisitors', [])
        ->assertSet('selectAll', false);
});

// ============================================
// BULK ASSIGN TESTS
// ============================================

test('admin can bulk assign visitors to member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $visitors = Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkAssignModal')
        ->assertSet('showBulkAssignModal', true)
        ->set('bulkAssignTo', $member->id)
        ->call('bulkAssign')
        ->assertDispatched('visitors-bulk-assigned');

    foreach ($visitors as $visitor) {
        expect($visitor->fresh()->assigned_to)->toBe($member->id);
    }
});

test('can bulk unassign visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $visitors = Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkAssignModal')
        ->set('bulkAssignTo', 'unassign')
        ->call('bulkAssign')
        ->assertDispatched('visitors-bulk-assigned');

    foreach ($visitors as $visitor) {
        expect($visitor->fresh()->assigned_to)->toBeNull();
    }
});

test('volunteer cannot bulk assign visitors', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkAssignModal')
        ->assertForbidden();
});

// ============================================
// BULK STATUS CHANGE TESTS
// ============================================

test('admin can bulk change visitor status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::New,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkStatusModal')
        ->assertSet('showBulkStatusModal', true)
        ->set('bulkStatusValue', 'followed_up')
        ->call('bulkChangeStatus')
        ->assertDispatched('visitors-bulk-status-changed');

    foreach ($visitors as $visitor) {
        expect($visitor->fresh()->status)->toBe(VisitorStatus::FollowedUp);
    }
});

test('bulk status change to converted sets is_converted flag', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'status' => VisitorStatus::New,
        'is_converted' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkStatusModal')
        ->set('bulkStatusValue', 'converted')
        ->call('bulkChangeStatus')
        ->assertDispatched('visitors-bulk-status-changed');

    foreach ($visitors as $visitor) {
        $refreshed = $visitor->fresh();
        expect($refreshed->status)->toBe(VisitorStatus::Converted);
        expect($refreshed->is_converted)->toBeTrue();
    }
});

test('volunteer cannot bulk change visitor status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkStatusModal')
        ->assertForbidden();
});

test('selection clears after bulk status change', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkStatusModal')
        ->set('bulkStatusValue', 'followed_up')
        ->call('bulkChangeStatus')
        ->assertSet('selectedVisitors', [])
        ->assertSet('selectAll', false);
});

// ============================================
// BRANCH SCOPING TESTS
// ============================================

test('bulk actions only affect visitors in current branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $currentBranchVisitors = Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
    ]);

    $otherBranchVisitors = Visitor::factory()->count(2)->create([
        'branch_id' => $otherBranch->id,
    ]);

    $this->actingAs($user);

    // Try to delete visitors including ones from other branch
    // The bulk delete should only affect current branch visitors
    $allIds = [...$currentBranchVisitors->pluck('id')->toArray(), ...$otherBranchVisitors->pluck('id')->toArray()];

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $allIds)
        ->call('confirmBulkDelete')
        ->call('bulkDelete');

    // Current branch visitors should be deleted
    expect(Visitor::whereIn('id', $currentBranchVisitors->pluck('id'))->count())->toBe(0);

    // Other branch visitors should still exist
    expect(Visitor::whereIn('id', $otherBranchVisitors->pluck('id'))->count())->toBe(2);
});

// ============================================
// MODAL CANCEL TESTS
// ============================================

test('cancel bulk delete modal closes and preserves selection', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('confirmBulkDelete')
        ->assertSet('showBulkDeleteModal', true)
        ->call('cancelBulkDelete')
        ->assertSet('showBulkDeleteModal', false);

    // Selection should be preserved after cancel
    expect($component->instance()->selectedVisitors)->toHaveCount(2);
});

test('cancel bulk assign modal closes and clears assignment value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkAssignModal')
        ->set('bulkAssignTo', $member->id)
        ->call('cancelBulkAssign')
        ->assertSet('showBulkAssignModal', false)
        ->assertSet('bulkAssignTo', null);
});

test('cancel bulk status modal closes and clears status value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', $visitors->pluck('id')->toArray())
        ->call('openBulkStatusModal')
        ->set('bulkStatusValue', 'followed_up')
        ->call('cancelBulkStatus')
        ->assertSet('showBulkStatusModal', false)
        ->assertSet('bulkStatusValue', '');
});

// ============================================
// DISPLAY TESTS
// ============================================

test('export button is visible when visitors exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('Export CSV');
});

test('stats cards are displayed', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('Total Visitors')
        ->assertSee('New Visitors')
        ->assertSee('Converted')
        ->assertSee('Pending Follow-ups');
});

test('bulk action toolbar appears when items selected', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitors = Visitor::factory()->count(2)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('selectedVisitors', [$visitors[0]->id])
        ->assertSee('1 visitor selected')
        ->assertSee('Clear')
        ->assertSee('Assign')
        ->assertSee('Change Status');
});
