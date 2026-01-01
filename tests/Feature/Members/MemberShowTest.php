<?php

use App\Enums\BranchRole;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Livewire\Members\MemberShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
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

    // Create a test member
    $this->member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'middle_name' => 'Michael',
        'email' => 'john.doe@example.com',
        'phone' => '0241234567',
        'date_of_birth' => '1990-05-15',
        'gender' => Gender::Male,
        'marital_status' => MaritalStatus::Married,
        'status' => MembershipStatus::Active,
        'address' => '123 Main Street',
        'city' => 'Accra',
        'state' => 'Greater Accra',
        'zip' => '00233',
        'country' => 'Ghana',
        'joined_at' => '2024-01-01',
        'baptized_at' => '2024-06-15',
        'notes' => 'A very active member of the congregation.',
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view member show page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/members/{$this->member->id}")
        ->assertOk()
        ->assertSeeLivewire(MemberShow::class);
});

test('user without branch access cannot view member show page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/members/{$this->member->id}")
        ->assertForbidden();
});

test('unauthenticated user cannot view member show page', function () {
    $this->get("/branches/{$this->branch->id}/members/{$this->member->id}")
        ->assertRedirect('/login');
});

// ============================================
// ROLE ACCESS TESTS
// ============================================

test('admin can view member details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('John')
        ->assertSee('Doe');
});

test('manager can view member details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('John');
});

test('staff can view member details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('John');
});

test('volunteer can view member details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('John');
});

// ============================================
// DATA DISPLAY TESTS
// ============================================

test('member show displays personal information', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('John')
        ->assertSee('Doe')
        ->assertSee('Male')
        ->assertSee('Married')
        ->assertSee('May 15, 1990');
});

test('member show displays contact information', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('john.doe@example.com')
        ->assertSee('0241234567');
});

test('member show displays address', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('123 Main Street')
        ->assertSee('Accra')
        ->assertSee('Greater Accra')
        ->assertSee('Ghana');
});

test('member show displays church information', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('Jan 01, 2024')
        ->assertSee('Jun 15, 2024')
        ->assertSee($this->branch->name);
});

test('member show displays notes', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('A very active member of the congregation.');
});

test('member show displays status badge', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSee('Active');
});

// ============================================
// AUTHORIZATION COMPUTED PROPERTIES TESTS
// ============================================

test('canEdit returns true for admin', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member]);
    expect($component->instance()->canEdit)->toBeTrue();
});

test('canEdit returns true for staff', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member]);
    expect($component->instance()->canEdit)->toBeTrue();
});

test('canEdit returns false for volunteer', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member]);
    expect($component->instance()->canEdit)->toBeFalse();
});

test('canDelete returns true for admin', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member]);
    expect($component->instance()->canDelete)->toBeTrue();
});

test('canDelete returns false for staff', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member]);
    expect($component->instance()->canDelete)->toBeFalse();
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot view member from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertForbidden();
});
