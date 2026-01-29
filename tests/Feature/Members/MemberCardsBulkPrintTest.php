<?php

use App\Enums\BranchRole;
use App\Livewire\Members\MemberCardsBulkPrint;
use App\Livewire\Members\MemberIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// BULK PRINT COMPONENT TESTS
// ============================================

test('bulk print component loads members from query string', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    // Simulate query string by setting request
    request()->merge(['ids' => $members->pluck('id')->implode(',')]);

    Livewire::test(MemberCardsBulkPrint::class, ['branch' => $this->branch])
        ->assertSee($members[0]->first_name)
        ->assertSee($members[1]->first_name)
        ->assertSee($members[2]->first_name);
});

test('bulk print component shows empty state when no ids provided', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberCardsBulkPrint::class, ['branch' => $this->branch])
        ->assertSee('No members selected');
});

test('bulk print component only shows members from correct branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $otherMember = Member::factory()->create([
        'primary_branch_id' => $otherBranch->id,
    ]);

    $this->actingAs($user);

    request()->merge(['ids' => $member->id.','.$otherMember->id]);

    Livewire::test(MemberCardsBulkPrint::class, ['branch' => $this->branch])
        ->assertSee($member->first_name)
        ->assertDontSee($otherMember->first_name);
});

// ============================================
// MEMBER INDEX SELECTION TESTS
// ============================================

test('member index can select individual members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSet('selectedMembers', [])
        ->set('selectedMembers', [$members[0]->id])
        ->assertSet('selectedMembers', [$members[0]->id])
        ->assertSet('selectAll', false);
});

test('member index select all selects all members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('selectAll', true)
        ->assertSet('selectedMembers', $members->pluck('id')->toArray());
});

test('member index clear selection clears all selected members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('selectAll', true)
        ->assertSet('selectedMembers', $members->pluck('id')->toArray())
        ->call('clearSelection')
        ->assertSet('selectedMembers', [])
        ->assertSet('selectAll', false);
});

test('member index shows selection toolbar when members selected', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('selectedMembers', [$members[0]->id, $members[1]->id])
        ->assertSee('2 selected')
        ->assertSee('Print Selected Cards');
});

test('member index print selected cards redirects with correct ids', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $selectedIds = [$members[0]->id, $members[1]->id];

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->set('selectedMembers', $selectedIds)
        ->call('printSelectedCards')
        ->assertRedirect(route('members.cards-print', [
            'branch' => $this->branch,
            'ids' => implode(',', $selectedIds),
        ]));
});

test('member index print all cards redirects with all member ids', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $members = Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->call('printAllCards')
        ->assertRedirect(route('members.cards-print', [
            'branch' => $this->branch,
            'ids' => $members->pluck('id')->implode(','),
        ]));
});

test('member index shows print all cards button when members exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertSee('Print All Cards');
});

test('member index does not show print all cards button when no members', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberIndex::class, ['branch' => $this->branch])
        ->assertDontSee('Print All Cards');
});
