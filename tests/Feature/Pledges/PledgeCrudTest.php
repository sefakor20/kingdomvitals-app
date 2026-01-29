<?php

use App\Enums\BranchRole;
use App\Enums\PledgeStatus;
use App\Livewire\Pledges\PledgeIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgeCampaign;
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
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view pledges page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/pledges")
        ->assertOk()
        ->assertSeeLivewire(PledgeIndex::class);
});

test('user without branch access cannot view pledges page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/pledges")
        ->assertForbidden();
});

test('unauthenticated user cannot view pledges page', function (): void {
    $this->get("/branches/{$this->branch->id}/pledges")
        ->assertRedirect('/login');
});

// ============================================
// VIEW PLEDGES AUTHORIZATION TESTS
// ============================================

test('admin can view pledges list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->assertSee($pledge->campaign_name);
});

test('volunteer can view pledges list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->assertSee($pledge->campaign_name);
});

// ============================================
// CREATE PLEDGE AUTHORIZATION TESTS
// ============================================

test('admin can create a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('member_id', $member->id)
        ->set('campaign_name', 'Building Fund 2025')
        ->set('amount', '5000.00')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('pledge-created');

    $pledge = Pledge::where('campaign_name', 'Building Fund 2025')->first();
    expect($pledge)->not->toBeNull();
    expect($pledge->status)->toBe(PledgeStatus::Active);
    expect((float) $pledge->amount_fulfilled)->toBe(0.0);
});

test('manager can create a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('member_id', $member->id)
        ->set('campaign_name', 'Missions 2025')
        ->set('amount', '1000.00')
        ->set('frequency', 'one_time')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Pledge::where('campaign_name', 'Missions 2025')->exists())->toBeTrue();
});

test('staff can create a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('member_id', $member->id)
        ->set('campaign_name', 'Youth Ministry')
        ->set('amount', '500.00')
        ->set('frequency', 'weekly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Pledge::where('campaign_name', 'Youth Ministry')->exists())->toBeTrue();
});

test('volunteer cannot create a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE PLEDGE AUTHORIZATION TESTS
// ============================================

test('admin can update a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('edit', $pledge)
        ->assertSet('showEditModal', true)
        ->set('campaign_name', 'Updated Campaign')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('pledge-updated');

    expect($pledge->fresh()->campaign_name)->toBe('Updated Campaign');
});

test('volunteer cannot update a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('edit', $pledge)
        ->assertForbidden();
});

// ============================================
// DELETE PLEDGE AUTHORIZATION TESTS
// ============================================

test('admin can delete a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);
    $pledgeId = $pledge->id;

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $pledge)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('pledge-deleted');

    expect(Pledge::find($pledgeId))->toBeNull();
});

test('manager can delete a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);
    $pledgeId = $pledge->id;

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $pledge)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Pledge::find($pledgeId))->toBeNull();
});

test('staff cannot delete a pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $pledge)
        ->assertForbidden();
});

// ============================================
// PAYMENT RECORDING TESTS
// ============================================

test('admin can record payment for active pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->unfulfilled()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->assertSet('showPaymentModal', true)
        ->set('paymentAmount', '250.00')
        ->call('recordPayment')
        ->assertSet('showPaymentModal', false)
        ->assertDispatched('payment-recorded');

    $pledge->refresh();
    expect((float) $pledge->amount_fulfilled)->toBe(250.0);
    expect($pledge->status)->toBe(PledgeStatus::Active);
});

test('manager can record payment for pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->unfulfilled()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->set('paymentAmount', '500.00')
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect((float) $pledge->fresh()->amount_fulfilled)->toBe(500.0);
});

test('staff can record payment for pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->unfulfilled()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->set('paymentAmount', '100.00')
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect((float) $pledge->fresh()->amount_fulfilled)->toBe(100.0);
});

test('volunteer cannot record payment for pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->assertForbidden();
});

test('pledge auto-completes when fully paid', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
        'amount_fulfilled' => 800,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->set('paymentAmount', '200.00')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $pledge->refresh();
    expect((float) $pledge->amount_fulfilled)->toBe(1000.0);
    expect($pledge->status)->toBe(PledgeStatus::Completed);
});

test('pledge auto-completes when overpaid', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
        'amount_fulfilled' => 900,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->set('paymentAmount', '200.00')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $pledge->refresh();
    expect((float) $pledge->amount_fulfilled)->toBe(1100.0);
    expect($pledge->status)->toBe(PledgeStatus::Completed);
});

// ============================================
// STATUS TRANSITION TESTS
// ============================================

test('can pause active pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('pausePledge', $pledge)
        ->assertDispatched('pledge-paused');

    expect($pledge->fresh()->status)->toBe(PledgeStatus::Paused);
});

test('can resume paused pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->paused()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('resumePledge', $pledge)
        ->assertDispatched('pledge-resumed');

    expect($pledge->fresh()->status)->toBe(PledgeStatus::Active);
});

test('can cancel active pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('cancelPledge', $pledge)
        ->assertDispatched('pledge-cancelled');

    expect($pledge->fresh()->status)->toBe(PledgeStatus::Cancelled);
});

test('can cancel paused pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->paused()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('cancelPledge', $pledge)
        ->assertDispatched('pledge-cancelled');

    expect($pledge->fresh()->status)->toBe(PledgeStatus::Cancelled);
});

test('cannot pause non-active pledge', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->completed()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('pausePledge', $pledge);

    // Status should remain completed
    expect($pledge->fresh()->status)->toBe(PledgeStatus::Completed);
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search pledges by campaign name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $buildingPledge = Pledge::factory()->forCampaign('Building Fund')->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $missionsPledge = Pledge::factory()->forCampaign('Missions Outreach')->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->set('search', 'Building')
        ->assertSee('Building Fund')
        // Assert the other pledge row is not displayed in the table
        // We check for the pledge ID in wire:key which is unique per row
        ->assertSeeHtml('pledge-'.$buildingPledge->id)
        ->assertDontSeeHtml('pledge-'.$missionsPledge->id);
});

test('can filter pledges by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    Pledge::factory()->completed()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active');

    expect($component->instance()->pledges->count())->toBe(1);
    expect($component->instance()->pledges->first()->status)->toBe(PledgeStatus::Active);
});

test('can filter pledges by member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
    ]);
    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->set('memberFilter', $member1->id);

    expect($component->instance()->pledges->count())->toBe(1);
    expect($component->instance()->pledges->first()->member_id)->toBe($member1->id);
});

test('can filter pledges by campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Create campaigns
    $buildingFundCampaign = PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Building Fund',
    ]);

    $missionsCampaign = PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Missions',
    ]);

    // Create pledges linked to campaigns
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'pledge_campaign_id' => $buildingFundCampaign->id,
        'campaign_name' => 'Building Fund',
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'pledge_campaign_id' => $missionsCampaign->id,
        'campaign_name' => 'Missions',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->set('campaignFilter', $buildingFundCampaign->id);

    expect($component->instance()->pledges->count())->toBe(1);
    expect($component->instance()->pledges->first()->pledge_campaign_id)->toBe($buildingFundCampaign->id);
});

// ============================================
// STATS TESTS
// ============================================

test('pledge stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
        'amount_fulfilled' => 500,
    ]);

    Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 2000,
        'amount_fulfilled' => 1000,
    ]);

    Pledge::factory()->completed()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 500,
        'amount_fulfilled' => 500,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PledgeIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->pledgeStats;

    expect($stats['active'])->toBe(2);
    expect($stats['totalPledged'])->toBe(3500.0);
    expect($stats['totalFulfilled'])->toBe(2000.0);
    expect($stats['fulfillmentRate'])->toBe(57.1);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('member is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('member_id', '')
        ->set('campaign_name', 'Test Campaign')
        ->set('amount', '1000')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['member_id']);
});

test('campaign name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('member_id', $member->id)
        ->set('campaign_name', '')
        ->set('amount', '1000')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['campaign_name']);
});

test('amount is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('member_id', $member->id)
        ->set('campaign_name', 'Test Campaign')
        ->set('amount', '')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['amount']);
});

test('payment amount is required when recording payment', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->set('paymentAmount', '')
        ->call('recordPayment')
        ->assertHasErrors(['paymentAmount']);
});

// ============================================
// MODAL CANCEL TESTS
// ============================================

test('cancel create modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('campaign_name', 'Test')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('campaign_name', '');
});

test('cancel payment modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->assertSet('showPaymentModal', true)
        ->set('paymentAmount', '100')
        ->call('cancelPayment')
        ->assertSet('showPaymentModal', false)
        ->assertSet('recordingPaymentFor', null)
        ->assertSet('paymentAmount', '');
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no pledges exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->assertSee('No pledges found');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Pledge');
});

test('create button is hidden for volunteers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(PledgeIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->canCreate)->toBeFalse();
});

// ============================================
// PROGRESS CALCULATION TESTS
// ============================================

test('progress bar shows correct completion percentage', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000,
        'amount_fulfilled' => 750,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->assertSee('75%');
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update pledge from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);
    $pledge = Pledge::factory()->create([
        'branch_id' => $otherBranch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('edit', $pledge)
        ->assertForbidden();
});

test('user cannot record payment for pledge from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $otherBranch->id]);
    $pledge = Pledge::factory()->active()->create([
        'branch_id' => $otherBranch->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(PledgeIndex::class, ['branch' => $this->branch])
        ->call('openPaymentModal', $pledge)
        ->assertForbidden();
});
