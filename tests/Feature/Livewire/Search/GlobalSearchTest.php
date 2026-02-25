<?php

use App\Enums\BranchRole;
use App\Livewire\Search\GlobalSearch;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Clear caches
    Cache::flush();
    app()->forgetInstance(\App\Services\PlanAccessService::class);
    app()->forgetInstance(\App\Services\BranchContextService::class);

    $this->branch = Branch::factory()->main()->create();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Give user access to branch
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
        'is_primary' => true,
    ]);

    // Set branch in session
    session(['current_branch_id' => $this->branch->id]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('global search component can be rendered', function (): void {
    Livewire::test(GlobalSearch::class)
        ->assertStatus(200)
        ->assertSee('Search');
});

test('modal opens when openModal is called', function (): void {
    Livewire::test(GlobalSearch::class)
        ->assertSet('showModal', false)
        ->call('openModal')
        ->assertSet('showModal', true);
});

test('modal closes and search resets when closeModal is called', function (): void {
    Livewire::test(GlobalSearch::class)
        ->set('showModal', true)
        ->set('search', 'test')
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('search', '');
});

test('search requires at least 2 characters', function (): void {
    Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'a')
        ->assertSee('Type at least 2 characters');
});

test('search finds members by first name', function (): void {
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Robert',
        'middle_name' => null,
        'last_name' => 'Smith',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Mary',
        'middle_name' => null,
        'last_name' => 'Doe',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Robert');

    $results = $component->get('results');

    expect($results)->toHaveKey('members');
    expect($results['members']['items'])->toHaveCount(1);
    expect($results['members']['items'][0]['title'])->toBe('Robert Smith');
});

test('search finds members by last name', function (): void {
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Alice',
        'last_name' => 'Johnson',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Johnson');

    $results = $component->get('results');

    expect($results)->toHaveKey('members');
    expect($results['members']['items'][0]['title'])->toBe('Alice Johnson');
});

test('search finds members by email', function (): void {
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Bob',
        'last_name' => 'Wilson',
        'email' => 'bob.wilson@example.com',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'bob.wilson');

    $results = $component->get('results');

    expect($results)->toHaveKey('members');
    expect($results['members']['items'][0]['title'])->toBe('Bob Wilson');
});

test('search finds visitors', function (): void {
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Visiting',
        'last_name' => 'Guest',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Visiting');

    $results = $component->get('results');

    expect($results)->toHaveKey('visitors');
    expect($results['visitors']['items'][0]['title'])->toBe('Visiting Guest');
});

test('search finds services', function (): void {
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Morning Service',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Sunday');

    $results = $component->get('results');

    expect($results)->toHaveKey('services');
    expect($results['services']['items'][0]['title'])->toBe('Sunday Morning Service');
});

test('search only returns results from current branch', function (): void {
    $otherBranch = Branch::factory()->create();

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Current',
        'middle_name' => null,
        'last_name' => 'Branch',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $otherBranch->id,
        'first_name' => 'Current',
        'middle_name' => null,
        'last_name' => 'Other',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Current');

    $results = $component->get('results');

    expect($results['members']['items'])->toHaveCount(1);
    expect($results['members']['items'][0]['title'])->toBe('Current Branch');
});

test('selectResult navigates to member show page', function (): void {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'last_name' => 'Member',
    ]);

    Livewire::test(GlobalSearch::class)
        ->call('selectResult', 'members', $member->id)
        ->assertRedirect(route('members.show', ['branch' => $this->branch->id, 'member' => $member->id]));
});

test('selectResult navigates to visitor show page', function (): void {
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Test',
        'last_name' => 'Visitor',
    ]);

    Livewire::test(GlobalSearch::class)
        ->call('selectResult', 'visitors', $visitor->id)
        ->assertRedirect(route('visitors.show', ['branch' => $this->branch->id, 'visitor' => $visitor->id]));
});

test('search limits results per type to 5', function (): void {
    // Create 7 members
    Member::factory()->count(7)->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Same',
    ]);

    $component = Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'Same');

    $results = $component->get('results');

    expect($results['members']['items'])->toHaveCount(5);
});

test('no results message shown when search yields nothing', function (): void {
    Livewire::test(GlobalSearch::class)
        ->set('currentBranchId', $this->branch->id)
        ->call('openModal')
        ->set('search', 'NonExistentPerson12345')
        ->assertSee('No results found');
});

test('getTypeLabel returns correct labels', function (): void {
    $component = new GlobalSearch;

    expect($component->getTypeLabel('members'))->toBe('Members');
    expect($component->getTypeLabel('visitors'))->toBe('Visitors');
    expect($component->getTypeLabel('services'))->toBe('Services');
    expect($component->getTypeLabel('households'))->toBe('Households');
    expect($component->getTypeLabel('clusters'))->toBe('Clusters');
    expect($component->getTypeLabel('equipment'))->toBe('Equipment');
    expect($component->getTypeLabel('prayer_requests'))->toBe('Prayer Requests');
});
