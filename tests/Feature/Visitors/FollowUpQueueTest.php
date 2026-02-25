<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Livewire\Visitors\FollowUpQueue;
use App\Models\SubscriptionPlan;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create a subscription plan with visitors module enabled
    $plan = SubscriptionPlan::create([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price_monthly' => 10.00,
        'price_annual' => 100.00,
        'enabled_modules' => ['visitors', 'members'],
    ]);    // Clear cached plan data
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);
    $this->branch = Branch::factory()->main()->create();
    $this->visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    // Create admin user for most tests
    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// AUTHORIZATION TESTS
// ============================================

test('admin can access follow-up queue', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    // If we get here without exception, authorization passed
    expect($component->instance()->branch->id)->toBe($this->branch->id);
});

test('manager can access follow-up queue', function (): void {
    $manager = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $manager->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($manager);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->branch->id)->toBe($this->branch->id);
});

test('staff can access follow-up queue', function (): void {
    $staff = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($staff);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->branch->id)->toBe($this->branch->id);
});

test('volunteer can access follow-up queue', function (): void {
    $volunteer = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $volunteer->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($volunteer);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->branch->id)->toBe($this->branch->id);
});

test('user without branch access cannot view follow-up queue', function (): void {
    $otherUser = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $otherUser->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($otherUser);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->assertForbidden();
});

// ============================================
// STATS CALCULATION TESTS
// ============================================

test('stats calculate correctly with no follow-ups', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    $stats = $component->instance()->stats;

    expect($stats['total'])->toBe(0);
    expect($stats['overdue'])->toBe(0);
    expect($stats['dueToday'])->toBe(0);
    expect($stats['upcoming'])->toBe(0);
});

test('stats calculate correctly with mixed follow-ups', function (): void {
    // Create overdue follow-up
    VisitorFollowUp::factory()->overdue()->create([
        'visitor_id' => $this->visitor->id,
    ]);

    // Create due today follow-up
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    // Create upcoming follow-up
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDays(3),
    ]);

    // Create completed follow-up (should not be counted)
    VisitorFollowUp::factory()->completed()->create([
        'visitor_id' => $this->visitor->id,
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    $stats = $component->instance()->stats;

    expect($stats['total'])->toBe(3);
    expect($stats['overdue'])->toBe(1);
    expect($stats['dueToday'])->toBe(1);
    expect($stats['upcoming'])->toBe(1);
});

// ============================================
// GROUPING TESTS
// ============================================

test('overdue follow-ups are grouped correctly', function (): void {
    VisitorFollowUp::factory()->overdue()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->subDays(2),
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->overdueFollowUps)->toHaveCount(1);
    expect($component->instance()->dueTodayFollowUps)->toHaveCount(0);
    expect($component->instance()->upcomingFollowUps)->toHaveCount(0);
});

test('due today follow-ups are grouped correctly', function (): void {
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->overdueFollowUps)->toHaveCount(0);
    expect($component->instance()->dueTodayFollowUps)->toHaveCount(1);
    expect($component->instance()->upcomingFollowUps)->toHaveCount(0);
});

test('upcoming follow-ups are grouped correctly', function (): void {
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDays(3),
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->overdueFollowUps)->toHaveCount(0);
    expect($component->instance()->dueTodayFollowUps)->toHaveCount(0);
    expect($component->instance()->upcomingFollowUps)->toHaveCount(1);
});

test('follow-ups beyond 7 days are not shown in upcoming', function (): void {
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDays(10),
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->upcomingFollowUps)->toHaveCount(0);
});

// ============================================
// FILTER TESTS
// ============================================

test('search filter works on visitor name', function (): void {
    $visitor1 = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $visitor2 = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $visitor1->id,
        'scheduled_at' => now()->addDay(),
    ]);
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $visitor2->id,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('search', 'John');

    expect($component->instance()->upcomingFollowUps)->toHaveCount(1);
    expect($component->instance()->upcomingFollowUps->first()->visitor->first_name)->toBe('John');
});

test('type filter works', function (): void {
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'type' => FollowUpType::Call,
    ]);
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'type' => FollowUpType::Sms,
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('typeFilter', 'call');

    expect($component->instance()->upcomingFollowUps)->toHaveCount(1);
    expect($component->instance()->upcomingFollowUps->first()->type)->toBe(FollowUpType::Call);
});

test('member filter works for assigned follow-ups', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'performed_by' => $member->id,
    ]);
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'performed_by' => null,
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('memberFilter', $member->id);

    expect($component->instance()->upcomingFollowUps)->toHaveCount(1);
});

test('member filter works for unassigned follow-ups', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'performed_by' => $member->id,
    ]);
    VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
        'performed_by' => null,
    ]);

    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('memberFilter', 'unassigned');

    expect($component->instance()->upcomingFollowUps)->toHaveCount(1);
    expect($component->instance()->upcomingFollowUps->first()->performed_by)->toBeNull();
});

test('clear filters resets all filters', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('search', 'test')
        ->set('typeFilter', 'call')
        ->set('memberFilter', 'unassigned')
        ->call('clearFilters');

    expect($component->instance()->search)->toBe('');
    expect($component->instance()->typeFilter)->toBeNull();
    expect($component->instance()->memberFilter)->toBeNull();
});

// ============================================
// COMPLETE FOLLOW-UP TESTS
// ============================================

test('admin can complete follow-up from queue', function (): void {
    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openCompleteModal', $followUp)
        ->assertSet('showCompleteModal', true)
        ->set('completionOutcome', 'successful')
        ->set('completionNotes', 'Completed from queue')
        ->call('completeFollowUp')
        ->assertSet('showCompleteModal', false)
        ->assertDispatched('follow-up-completed');

    $followUp->refresh();
    expect($followUp->outcome)->toBe(FollowUpOutcome::Successful);
    expect($followUp->notes)->toBe('Completed from queue');
    expect($followUp->completed_at)->not->toBeNull();
});

test('completing follow-up updates visitor stats', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'follow_up_count' => 0,
        'last_follow_up_at' => null,
    ]);

    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $visitor->id,
        'scheduled_at' => now(),
    ]);

    $this->actingAs($this->admin);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openCompleteModal', $followUp)
        ->set('completionOutcome', 'successful')
        ->call('completeFollowUp');

    $visitor->refresh();
    expect($visitor->follow_up_count)->toBe(1);
    expect($visitor->last_follow_up_at)->not->toBeNull();
});

test('staff can complete follow-up', function (): void {
    $staff = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    $this->actingAs($staff);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openCompleteModal', $followUp)
        ->set('completionOutcome', 'no_answer')
        ->call('completeFollowUp')
        ->assertDispatched('follow-up-completed');

    $followUp->refresh();
    expect($followUp->outcome)->toBe(FollowUpOutcome::NoAnswer);
});

test('volunteer cannot complete follow-up', function (): void {
    $volunteer = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $volunteer->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    $this->actingAs($volunteer);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openCompleteModal', $followUp)
        ->assertForbidden();
});

// ============================================
// RESCHEDULE TESTS
// ============================================

test('admin can reschedule follow-up', function (): void {
    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now(),
    ]);

    $newDate = now()->addDays(3)->format('Y-m-d\TH:i');

    $this->actingAs($this->admin);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openRescheduleModal', $followUp)
        ->assertSet('showRescheduleModal', true)
        ->set('rescheduleDate', $newDate)
        ->call('rescheduleFollowUp')
        ->assertSet('showRescheduleModal', false)
        ->assertDispatched('follow-up-rescheduled');

    $followUp->refresh();
    expect($followUp->scheduled_at->format('Y-m-d'))->toBe(now()->addDays(3)->format('Y-m-d'));
});

test('cannot reschedule to past date', function (): void {
    $followUp = VisitorFollowUp::factory()->scheduled()->create([
        'visitor_id' => $this->visitor->id,
        'scheduled_at' => now()->addDay(),
    ]);

    $pastDate = now()->subDay()->format('Y-m-d\TH:i');

    $this->actingAs($this->admin);

    Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->call('openRescheduleModal', $followUp)
        ->set('rescheduleDate', $pastDate)
        ->call('rescheduleFollowUp')
        ->assertHasErrors(['rescheduleDate']);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('returns empty collections when no pending follow-ups', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->overdueFollowUps)->toHaveCount(0);
    expect($component->instance()->dueTodayFollowUps)->toHaveCount(0);
    expect($component->instance()->upcomingFollowUps)->toHaveCount(0);
});

test('hasActiveFilters returns true when filters are set', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch])
        ->set('search', 'nonexistent');

    expect($component->instance()->hasActiveFilters)->toBeTrue();
});

test('hasActiveFilters returns false when no filters set', function (): void {
    $this->actingAs($this->admin);

    $component = Livewire::test(FollowUpQueue::class, ['branch' => $this->branch]);

    expect($component->instance()->hasActiveFilters)->toBeFalse();
});
