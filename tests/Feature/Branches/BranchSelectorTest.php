<?php

use App\Enums\BranchRole;
use App\Livewire\Branches\BranchSelector;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('branch selector shows user accessible branches', function (): void {
    $user = User::factory()->create();
    $branch1 = Branch::factory()->main()->create(['name' => 'Main Campus']);
    $branch2 = Branch::factory()->create(['name' => 'West Campus']);
    $branch3 = Branch::factory()->create(['name' => 'East Campus']); // No access

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch1->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch2->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchSelector::class)
        ->assertSee('Main Campus')
        ->assertSee('West Campus')
        ->assertDontSee('East Campus');
});

test('branch selector initializes with primary branch', function (): void {
    $user = User::factory()->create();
    $branch1 = Branch::factory()->main()->create(['name' => 'Main Campus']);
    $branch2 = Branch::factory()->create(['name' => 'West Campus']);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch1->id,
        'role' => BranchRole::Staff,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch2->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchSelector::class)
        ->assertSet('currentBranchId', $branch2->id);
});

test('switching branch updates current branch id', function (): void {
    $user = User::factory()->create();
    $branch1 = Branch::factory()->main()->create();
    $branch2 = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch1->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch2->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchSelector::class)
        ->assertSet('currentBranchId', $branch1->id)
        ->call('switchBranch', $branch2->id)
        ->assertSet('currentBranchId', $branch2->id)
        ->assertDispatched('branch-switched');
});

test('cannot switch to branch without access', function (): void {
    $user = User::factory()->create();
    $branch1 = Branch::factory()->main()->create();
    $branch2 = Branch::factory()->create(); // No access

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branch1->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchSelector::class)
        ->assertSet('currentBranchId', $branch1->id)
        ->call('switchBranch', $branch2->id)
        ->assertSet('currentBranchId', $branch1->id); // Should not change
});

test('branch selector only shows active branches', function (): void {
    $user = User::factory()->create();
    $activeBranch = Branch::factory()->main()->create(['name' => 'Active Branch']);
    $inactiveBranch = Branch::factory()->inactive()->create(['name' => 'Inactive Branch']);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $activeBranch->id,
        'role' => BranchRole::Admin,
        'is_primary' => true,
    ]);

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $inactiveBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(BranchSelector::class)
        ->assertSee('Active Branch')
        ->assertDontSee('Inactive Branch');
});
