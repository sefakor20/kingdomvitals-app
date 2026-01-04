<?php

use App\Enums\BranchRole;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Livewire\Reports\Membership\MemberDemographics;
use App\Livewire\Reports\Membership\MemberDirectory;
use App\Livewire\Reports\Membership\MemberGrowthTrends;
use App\Livewire\Reports\Membership\NewMembersReport;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();

    $this->adminUser = User::factory()->create();
    $this->adminUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin->value,
    ]);

    $this->volunteerUser = User::factory()->create();
    $this->volunteerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer->value,
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// Member Directory Tests

test('admin can access member directory', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.membership.directory', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access member directory', function () {
    $this->actingAs($this->volunteerUser)
        ->get(route('reports.membership.directory', $this->branch))
        ->assertForbidden();
});

test('member directory shows total count', function () {
    Member::factory()->count(5)->create([
        'primary_branch_id' => $this->branch->id,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberDirectory::class, ['branch' => $this->branch]);

    expect($component->get('totalCount'))->toBe(5);
});

test('member directory can filter by status', function () {
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);

    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Inactive,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberDirectory::class, ['branch' => $this->branch])
        ->set('status', 'active');

    expect($component->get('members')->count())->toBe(3);
});

test('member directory can search by name', function () {
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberDirectory::class, ['branch' => $this->branch])
        ->set('search', 'John');

    expect($component->get('members')->count())->toBe(1);
});

// New Members Report Tests

test('admin can access new members report', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.membership.new-members', $this->branch))
        ->assertStatus(200);
});

test('new members report shows members within period', function () {
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now()->subDays(5),
    ]);

    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now()->subDays(45),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(NewMembersReport::class, ['branch' => $this->branch]);

    expect($component->get('totalNewMembers'))->toBe(3);
});

test('new members report can change period', function () {
    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now()->subDays(5),
    ]);

    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'joined_at' => now()->subDays(60),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(NewMembersReport::class, ['branch' => $this->branch])
        ->call('setPeriod', 90);

    expect($component->get('totalNewMembers'))->toBe(5);
});

// Demographics Report Tests

test('admin can access demographics report', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.membership.demographics', $this->branch))
        ->assertStatus(200);
});

test('demographics shows gender distribution', function () {
    Member::factory()->count(3)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'gender' => Gender::Male,
    ]);

    Member::factory()->count(2)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
        'gender' => Gender::Female,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberDemographics::class, ['branch' => $this->branch]);

    $genderData = $component->get('genderDistribution');
    expect($genderData['Male'])->toBe(3);
    expect($genderData['Female'])->toBe(2);
});

// Growth Trends Report Tests

test('admin can access growth trends report', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.membership.growth', $this->branch))
        ->assertStatus(200);
});

test('growth trends can change period', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberGrowthTrends::class, ['branch' => $this->branch])
        ->call('setMonths', 6);

    expect($component->get('months'))->toBe(6);
});

test('growth trends can toggle comparison', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(MemberGrowthTrends::class, ['branch' => $this->branch])
        ->call('toggleComparison');

    expect($component->get('showComparison'))->toBeTrue();
});
