<?php

use App\Enums\BranchRole;
use App\Livewire\Reports\ReportCenter;
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

test('admin can access report center', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.index', $this->branch))
        ->assertStatus(200);
});

test('manager can access report center', function () {
    $managerUser = User::factory()->create();
    $managerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager->value,
    ]);

    $this->actingAs($managerUser)
        ->get(route('reports.index', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access report center', function () {
    $this->actingAs($this->volunteerUser)
        ->get(route('reports.index', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('reports.index', $this->branch))
        ->assertRedirect(route('login'));
});

test('report center renders correctly', function () {
    Livewire::actingAs($this->adminUser)
        ->test(ReportCenter::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Membership')
        ->assertSee('Attendance')
        ->assertSee('Financial');
});

test('membership stats are computed correctly', function () {
    Member::factory()->count(5)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(ReportCenter::class, ['branch' => $this->branch]);

    expect($component->get('membershipStats')['total'])->toBe(5);
});
