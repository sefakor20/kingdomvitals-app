<?php

use App\Enums\BranchRole;
use App\Enums\CampaignStatus;
use App\Livewire\Pledges\CampaignIndex;
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

test('authenticated user with branch access can view campaigns page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/campaigns")
        ->assertOk()
        ->assertSeeLivewire(CampaignIndex::class);
});

test('user without branch access cannot view campaigns page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/campaigns")
        ->assertForbidden();
});

test('unauthenticated user cannot view campaigns page', function (): void {
    $this->get("/branches/{$this->branch->id}/campaigns")
        ->assertRedirect('/login');
});

// ============================================
// VIEW CAMPAIGNS AUTHORIZATION TESTS
// ============================================

test('admin can view campaigns list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->assertSee($campaign->name);
});

test('volunteer can view campaigns list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->assertSee($campaign->name);
});

// ============================================
// CREATE CAMPAIGN AUTHORIZATION TESTS
// ============================================

test('admin can create a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Building Fund 2026')
        ->set('description', 'New church building campaign')
        ->set('category', 'building_fund')
        ->set('goal_amount', '100000')
        ->set('goal_participants', '50')
        ->set('start_date', now()->format('Y-m-d'))
        ->set('end_date', now()->addYear()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('campaign-created');

    $campaign = PledgeCampaign::where('name', 'Building Fund 2026')->first();
    expect($campaign)->not->toBeNull();
    expect($campaign->status)->toBe(CampaignStatus::Active);
    expect((float) $campaign->goal_amount)->toBe(100000.0);
    expect($campaign->goal_participants)->toBe(50);
});

test('staff can create a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Missions 2026')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(PledgeCampaign::where('name', 'Missions 2026')->exists())->toBeTrue();
});

test('volunteer cannot create a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE CAMPAIGN AUTHORIZATION TESTS
// ============================================

test('admin can update a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('edit', $campaign)
        ->assertSet('showEditModal', true)
        ->set('name', 'Updated Campaign Name')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('campaign-updated');

    expect($campaign->fresh()->name)->toBe('Updated Campaign Name');
});

test('volunteer cannot update a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('edit', $campaign)
        ->assertForbidden();
});

// ============================================
// DELETE CAMPAIGN AUTHORIZATION TESTS
// ============================================

test('admin can delete a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);
    $campaignId = $campaign->id;

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $campaign)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('campaign-deleted');

    expect(PledgeCampaign::find($campaignId))->toBeNull();
});

test('staff cannot delete a campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $campaign)
        ->assertForbidden();
});

// ============================================
// STATUS TRANSITION TESTS
// ============================================

test('can activate draft campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->draft()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('activateCampaign', $campaign)
        ->assertDispatched('campaign-activated');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Active);
});

test('can complete active campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('completeCampaign', $campaign)
        ->assertDispatched('campaign-completed');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
});

test('can cancel active campaign', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $campaign = PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('cancelCampaign', $campaign)
        ->assertDispatched('campaign-cancelled');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Cancelled);
});

// ============================================
// CAMPAIGN PROGRESS TESTS
// ============================================

test('campaign progress is calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
        'goal_amount' => 10000,
        'goal_participants' => 10,
    ]);

    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'pledge_campaign_id' => $campaign->id,
        'member_id' => $member1->id,
        'amount' => 3000,
        'amount_fulfilled' => 1500,
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'pledge_campaign_id' => $campaign->id,
        'member_id' => $member2->id,
        'amount' => 2000,
        'amount_fulfilled' => 2000,
    ]);

    expect($campaign->totalPledged())->toBe(5000.0);
    expect($campaign->totalFulfilled())->toBe(3500.0);
    expect($campaign->participantCount())->toBe(2);
    expect($campaign->amountProgress())->toBe(50.0);
    expect($campaign->participantProgress())->toBe(20.0);
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search campaigns by name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $buildingCampaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Building Fund 2026',
    ]);

    $missionsCampaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Missions Outreach',
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->set('search', 'Building')
        ->assertSee('Building Fund 2026')
        ->assertSeeHtml('campaign-'.$buildingCampaign->id)
        ->assertDontSeeHtml('campaign-'.$missionsCampaign->id);
});

test('can filter campaigns by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
    ]);

    PledgeCampaign::factory()->completed()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active');

    expect($component->instance()->campaigns->count())->toBe(1);
    expect($component->instance()->campaigns->first()->status)->toBe(CampaignStatus::Active);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('campaign name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['name']);
});

test('start date is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Campaign')
        ->set('start_date', '')
        ->call('store')
        ->assertHasErrors(['start_date']);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no campaigns exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->assertSee('No campaigns found');
});

test('campaign stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'goal_amount' => 10000,
    ]);

    PledgeCampaign::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'goal_amount' => 5000,
    ]);

    PledgeCampaign::factory()->completed()->create([
        'branch_id' => $this->branch->id,
        'goal_amount' => 3000,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(CampaignIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->campaignStats;

    expect($stats['total'])->toBe(3);
    expect($stats['active'])->toBe(2);
    expect($stats['totalGoal'])->toBe(18000.0);
});

// ============================================
// VIEW DETAILS TESTS
// ============================================

test('can view campaign details with pledges', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $campaign = PledgeCampaign::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'pledge_campaign_id' => $campaign->id,
        'member_id' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class, ['branch' => $this->branch])
        ->call('viewDetails', $campaign)
        ->assertSet('showDetailModal', true)
        ->assertSet('viewingCampaign.id', $campaign->id);
});
