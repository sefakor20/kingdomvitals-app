<?php

use App\Enums\BranchRole;
use App\Enums\VisitorStatus;
use App\Livewire\Visitors\VisitorIndex;
use App\Livewire\Visitors\VisitorShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view visitors page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/visitors")
        ->assertOk()
        ->assertSeeLivewire(VisitorIndex::class);
});

test('user without branch access cannot view visitors page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/visitors")
        ->assertForbidden();
});

test('unauthenticated user cannot view visitors page', function (): void {
    $this->get("/branches/{$this->branch->id}/visitors")
        ->assertRedirect('/login');
});

// ============================================
// VIEW VISITORS AUTHORIZATION TESTS
// ============================================

test('admin can view visitors list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee($visitor->first_name)
        ->assertSee($visitor->last_name);
});

test('manager can view visitors list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee($visitor->first_name);
});

test('staff can view visitors list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee($visitor->first_name);
});

test('volunteer can view visitors list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee($visitor->first_name);
});

// ============================================
// CREATE VISITOR AUTHORIZATION TESTS
// ============================================

test('admin can create a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'John')
        ->set('last_name', 'Visitor')
        ->set('email', 'john.visitor@example.com')
        ->set('phone', '0241234567')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('visitor-created');

    expect(Visitor::where('email', 'john.visitor@example.com')->exists())->toBeTrue();
});

test('manager can create a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Visitor')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasNoErrors();

    expect(Visitor::where('first_name', 'Jane')->where('last_name', 'Visitor')->exists())->toBeTrue();
});

test('staff can create a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Staff')
        ->set('last_name', 'Created')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasNoErrors();

    expect(Visitor::where('first_name', 'Staff')->exists())->toBeTrue();
});

test('volunteer cannot create a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE VISITOR AUTHORIZATION TESTS
// ============================================

test('admin can update a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'Updated')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('visitor-updated');

    expect($visitor->fresh()->first_name)->toBe('Updated');
});

test('manager can update a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'ManagerUpdated')
        ->call('update')
        ->assertHasNoErrors();

    expect($visitor->fresh()->first_name)->toBe('ManagerUpdated');
});

test('staff can update a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertSet('showEditModal', true)
        ->set('first_name', 'StaffUpdated')
        ->call('update')
        ->assertHasNoErrors();

    expect($visitor->fresh()->first_name)->toBe('StaffUpdated');
});

test('volunteer cannot update a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertForbidden();
});

// ============================================
// DELETE VISITOR AUTHORIZATION TESTS
// ============================================

test('admin can delete a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $visitorId = $visitor->id;

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('visitor-deleted');

    expect(Visitor::find($visitorId))->toBeNull();
});

test('manager can delete a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $visitorId = $visitor->id;

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Visitor::find($visitorId))->toBeNull();
});

test('staff cannot delete a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertForbidden();
});

test('volunteer cannot delete a visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search visitors by first name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'SearchableJohn',
        'last_name' => 'Doe',
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'HiddenJane',
        'last_name' => 'Smith',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('search', 'SearchableJohn')
        ->assertSee('SearchableJohn')
        ->assertDontSee('HiddenJane');
});

test('can search visitors by email', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'EmailSearchUser',
        'email' => 'unique.search@example.com',
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'OtherEmailUser',
        'email' => 'different@example.com',
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('search', 'unique.search')
        ->assertSee('EmailSearchUser')
        ->assertDontSee('OtherEmailUser');
});

test('can filter visitors by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'NewVisitor',
        'status' => VisitorStatus::New,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'FollowedUpVisitor',
        'status' => VisitorStatus::FollowedUp,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'new')
        ->assertSee('NewVisitor')
        ->assertDontSee('FollowedUpVisitor');
});

test('can filter visitors by converted status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'ConvertedVisitor',
        'is_converted' => true,
        'status' => VisitorStatus::Converted,
    ]);

    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'NotConvertedVisitor',
        'is_converted' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->set('convertedFilter', 'yes')
        ->assertSee('ConvertedVisitor')
        ->assertDontSee('NotConvertedVisitor');
});

// ============================================
// CONVERT TO MEMBER TESTS
// ============================================

test('admin can convert visitor to member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $visitor)
        ->assertSet('showConvertModal', true)
        ->set('convertToMemberId', $member->id)
        ->call('convert')
        ->assertHasNoErrors()
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted');

    $visitor->refresh();
    expect($visitor->is_converted)->toBeTrue();
    expect($visitor->converted_member_id)->toBe($member->id);
    expect($visitor->status)->toBe(VisitorStatus::Converted);
});

test('manager can convert visitor to member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $visitor)
        ->set('convertToMemberId', $member->id)
        ->call('convert')
        ->assertHasNoErrors();

    $visitor->refresh();
    expect($visitor->is_converted)->toBeTrue();
});

test('volunteer cannot convert visitor to member', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $visitor)
        ->assertForbidden();
});

test('convert requires member selection', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $visitor)
        ->call('convert')
        ->assertHasErrors(['convertToMemberId']);
});

// ============================================
// ASSIGN MEMBER FOR FOLLOW-UP TESTS
// ============================================

test('can assign member for follow-up', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->set('assigned_to', $member->id)
        ->call('update')
        ->assertHasNoErrors();

    $visitor->refresh();
    expect($visitor->assigned_to)->toBe($member->id);
});

test('can unassign member from follow-up', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->set('assigned_to', '')
        ->call('update')
        ->assertHasNoErrors();

    $visitor->refresh();
    expect($visitor->assigned_to)->toBeNull();
});

// ============================================
// VALIDATION TESTS
// ============================================

test('first name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', '')
        ->set('last_name', 'Doe')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasErrors(['first_name']);
});

test('last name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', '')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasErrors(['last_name']);
});

test('visit date is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('visit_date', '')
        ->set('status', 'new')
        ->call('store')
        ->assertHasErrors(['visit_date']);
});

test('email must be valid format', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('email', 'invalid-email')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'new')
        ->call('store')
        ->assertHasErrors(['email']);
});

test('status must be valid value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('visit_date', now()->format('Y-m-d'))
        ->set('status', 'invalid-status')
        ->call('store')
        ->assertHasErrors(['status']);
});

// ============================================
// VISITOR SHOW PAGE TESTS
// ============================================

test('can view visitor details page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/visitors/{$visitor->id}")
        ->assertOk()
        ->assertSeeLivewire(VisitorShow::class);
});

test('can edit visitor from show page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $visitor])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('first_name', 'UpdatedFromShow')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false)
        ->assertDispatched('visitor-updated');

    $visitor->refresh();
    expect($visitor->first_name)->toBe('UpdatedFromShow');
});

test('can delete visitor from show page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $visitorId = $visitor->id;

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $visitor])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertDispatched('visitor-deleted')
        ->assertRedirect(route('visitors.index', $this->branch));

    expect(Visitor::find($visitorId))->toBeNull();
});

test('can convert visitor from show page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorShow::class, ['branch' => $this->branch, 'visitor' => $visitor])
        ->call('openConvertModal')
        ->assertSet('showConvertModal', true)
        ->set('convertToMemberId', $member->id)
        ->call('convert')
        ->assertHasNoErrors()
        ->assertSet('showConvertModal', false)
        ->assertDispatched('visitor-converted');

    $visitor->refresh();
    expect($visitor->is_converted)->toBeTrue();
    expect($visitor->converted_member_id)->toBe($member->id);
});

// ============================================
// MODAL CANCEL OPERATION TESTS
// ============================================

test('cancel create modal closes modal and resets form', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('first_name', 'Test')
        ->set('last_name', 'Name')
        ->set('email', 'test@example.com')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('first_name', '')
        ->assertSet('last_name', '')
        ->assertSet('email', '');
});

test('cancel edit modal closes modal and clears editing visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertSet('showEditModal', true)
        ->assertSet('editingVisitor.id', $visitor->id)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingVisitor', null);
});

test('cancel delete modal closes modal and clears deleting visitor', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingVisitor.id', $visitor->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingVisitor', null);
});

test('cancel convert modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $visitor = Visitor::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('openConvertModal', $visitor)
        ->assertSet('showConvertModal', true)
        ->call('cancelConvert')
        ->assertSet('showConvertModal', false)
        ->assertSet('convertingVisitor', null);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no visitors exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('No visitors found');
});

test('visitor table displays visitor information correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'first_name' => 'Display',
        'last_name' => 'Test',
        'email' => 'display.test@example.com',
        'phone' => '0241112233',
        'status' => VisitorStatus::New,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('Display')
        ->assertSee('Test')
        ->assertSee('display.test@example.com')
        ->assertSee('0241112233');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Visitor');
});

test('create button is hidden for volunteers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    // Test that the canCreate computed property returns false for volunteers
    $component = Livewire::test(VisitorIndex::class, ['branch' => $this->branch]);

    // Verify that canCreate is false
    expect($component->instance()->canCreate)->toBeFalse();

    // Verify volunteer cannot trigger create action
    $component->call('create')->assertForbidden();
});

test('assigned member is displayed in visitor list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'FollowUpAssigned',
        'last_name' => 'PersonMember',
    ]);

    $visitor = Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'assigned_to' => $member->id,
    ]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->assertSee('FollowUpAssigned')
        ->assertSee('PersonMember');
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update visitor from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Visitor belongs to other branch
    $visitor = Visitor::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('edit', $visitor)
        ->assertForbidden();
});

test('user cannot delete visitor from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Visitor belongs to other branch
    $visitor = Visitor::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(VisitorIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $visitor)
        ->assertForbidden();
});
