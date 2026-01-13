<?php

use App\Enums\BranchRole;
use App\Livewire\Children\ChildrenDirectory;
use App\Models\Tenant;
use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildEmergencyContact;
use App\Models\Tenant\ChildMedicalInfo;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
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

    // Load tenant routes for testing
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));

    // Create main branch
    $this->branch = Branch::factory()->main()->create();
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('authenticated user can view children directory', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/children")
        ->assertOk()
        ->assertSeeLivewire(ChildrenDirectory::class);
});

test('children directory shows only children members', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create child members
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Child',
        'last_name' => 'Member',
        'date_of_birth' => now()->subYears(5),
    ]);

    // Create adult member
    $adult = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Adult',
        'last_name' => 'Member',
        'date_of_birth' => now()->subYears(30),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch]);

    $children = $component->get('children');
    expect($children->pluck('id')->contains($child->id))->toBeTrue();
    expect($children->pluck('id')->contains($adult->id))->toBeFalse();
});

test('children can be filtered by age group', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $ageGroup = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Nursery',
    ]);

    $childInGroup = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(2),
        'age_group_id' => $ageGroup->id,
    ]);

    $childNotInGroup = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->set('ageGroupFilter', $ageGroup->id);

    $children = $component->get('children');
    expect($children->pluck('id')->contains($childInGroup->id))->toBeTrue();
    expect($children->pluck('id')->contains($childNotInGroup->id))->toBeFalse();
});

test('children can be filtered to show unassigned only', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $ageGroup = AgeGroup::factory()->create(['branch_id' => $this->branch->id]);

    $assignedChild = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(3),
        'age_group_id' => $ageGroup->id,
    ]);

    $unassignedChild = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->set('ageGroupFilter', 'unassigned');

    $children = $component->get('children');
    expect($children->pluck('id')->contains($assignedChild->id))->toBeFalse();
    expect($children->pluck('id')->contains($unassignedChild->id))->toBeTrue();
});

test('staff can assign age group to child', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $ageGroup = AgeGroup::factory()->create(['branch_id' => $this->branch->id]);
    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('openAssignAgeGroupModal', $child)
        ->assertSet('showAssignAgeGroupModal', true)
        ->set('selectedAgeGroupId', $ageGroup->id)
        ->call('assignAgeGroup')
        ->assertDispatched('age-group-assigned');

    expect($child->fresh()->age_group_id)->toBe($ageGroup->id);
});

test('staff can add emergency contact to child', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('openEmergencyContactsModal', $child)
        ->assertSet('showEmergencyContactsModal', true)
        ->call('openAddContactModal')
        ->assertSet('showAddContactModal', true)
        ->set('contactName', 'Jane Doe')
        ->set('contactRelationship', 'Mother')
        ->set('contactPhone', '555-1234')
        ->set('contactIsPrimary', true)
        ->set('contactCanPickup', true)
        ->call('addEmergencyContact')
        ->assertDispatched('contact-added');

    expect($child->emergencyContacts()->count())->toBe(1);
    expect($child->emergencyContacts()->first()->name)->toBe('Jane Doe');
    expect($child->emergencyContacts()->first()->is_primary)->toBeTrue();
});

test('can edit emergency contact', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $contact = ChildEmergencyContact::factory()->create([
        'member_id' => $child->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('openEmergencyContactsModal', $child)
        ->call('editContact', $contact)
        ->assertSet('showEditContactModal', true)
        ->set('contactName', 'Updated Name')
        ->call('updateContact')
        ->assertDispatched('contact-updated');

    expect($contact->fresh()->name)->toBe('Updated Name');
});

test('can delete emergency contact', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $contact = ChildEmergencyContact::factory()->create([
        'member_id' => $child->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('openEmergencyContactsModal', $child)
        ->call('confirmDeleteContact', $contact)
        ->assertSet('showDeleteContactModal', true)
        ->call('deleteContact')
        ->assertDispatched('contact-deleted');

    expect(ChildEmergencyContact::find($contact->id))->toBeNull();
});

test('staff can save medical info for child', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('openMedicalInfoModal', $child)
        ->assertSet('showMedicalInfoModal', true)
        ->set('allergies', 'Peanuts')
        ->set('bloodType', 'A+')
        ->set('emergencyInstructions', 'Call parent immediately')
        ->call('saveMedicalInfo')
        ->assertDispatched('medical-info-saved');

    $medicalInfo = $child->medicalInfo()->first();
    expect($medicalInfo)->not->toBeNull();
    expect($medicalInfo->allergies)->toBe('Peanuts');
    expect($medicalInfo->blood_type)->toBe('A+');
});

test('stats are computed correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $ageGroup = AgeGroup::factory()->create(['branch_id' => $this->branch->id]);

    // Create children with various configurations
    $childWithAll = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(3),
        'age_group_id' => $ageGroup->id,
    ]);
    ChildEmergencyContact::factory()->create(['member_id' => $childWithAll->id]);
    ChildMedicalInfo::factory()->create(['member_id' => $childWithAll->id]);

    $childUnassigned = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'age_group_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch]);

    $stats = $component->get('stats');
    expect($stats['total'])->toBe(2);
    expect($stats['unassigned'])->toBe(1);
    expect($stats['withEmergencyContact'])->toBe(1);
    expect($stats['withMedicalInfo'])->toBe(1);
});

test('search filters children by name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $john = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => now()->subYears(5),
    ]);

    $jane = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'date_of_birth' => now()->subYears(3),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->set('search', 'John');

    $children = $component->get('children');
    expect($children->pluck('id')->contains($john->id))->toBeTrue();
    expect($children->pluck('id')->contains($jane->id))->toBeFalse();
});

test('clear filters resets all filters', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->set('search', 'Test')
        ->set('ageGroupFilter', 'some-id')
        ->set('minAge', 5)
        ->set('maxAge', 10)
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('ageGroupFilter', '')
        ->assertSet('minAge', null)
        ->assertSet('maxAge', null);
});

test('can filter children by household', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $household = Household::factory()->create(['branch_id' => $this->branch->id]);

    $childInHousehold = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
        'household_id' => $household->id,
    ]);

    $childNotInHousehold = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(3),
        'household_id' => null,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->set('householdFilter', $household->id);

    $children = $component->get('children');
    expect($children->pluck('id')->contains($childInHousehold->id))->toBeTrue();
    expect($children->pluck('id')->contains($childNotInHousehold->id))->toBeFalse();
});

// ============================================
// CREATE CHILD TESTS
// ============================================

test('staff can create a new child', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('createChild')
        ->assertSet('showCreateChildModal', true)
        ->set('firstName', 'Test')
        ->set('lastName', 'Child')
        ->set('childDateOfBirth', now()->subYears(5)->format('Y-m-d'))
        ->set('childGender', 'male')
        ->call('storeChild')
        ->assertHasNoErrors()
        ->assertSet('showCreateChildModal', false)
        ->assertDispatched('child-created');

    expect(Member::where('first_name', 'Test')->where('last_name', 'Child')->exists())->toBeTrue();
});

test('create child validates required fields', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('createChild')
        ->set('firstName', '')
        ->set('lastName', '')
        ->set('childDateOfBirth', null)
        ->call('storeChild')
        ->assertHasErrors(['firstName', 'lastName', 'childDateOfBirth']);
});

test('create child validates date of birth must be under 18', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('createChild')
        ->set('firstName', 'Adult')
        ->set('lastName', 'Person')
        ->set('childDateOfBirth', now()->subYears(20)->format('Y-m-d'))
        ->call('storeChild')
        ->assertHasErrors(['childDateOfBirth']);
});

test('create child auto-assigns age group when not selected', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $ageGroup = AgeGroup::factory()->create([
        'branch_id' => $this->branch->id,
        'min_age' => 4,
        'max_age' => 6,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('createChild')
        ->set('firstName', 'Auto')
        ->set('lastName', 'Assign')
        ->set('childDateOfBirth', now()->subYears(5)->format('Y-m-d'))
        ->call('storeChild')
        ->assertHasNoErrors();

    $child = Member::where('first_name', 'Auto')->where('last_name', 'Assign')->first();
    expect($child->age_group_id)->toBe($ageGroup->id);
});

test('cancel create child closes modal and resets form', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('createChild')
        ->assertSet('showCreateChildModal', true)
        ->set('firstName', 'Test')
        ->call('cancelCreateChild')
        ->assertSet('showCreateChildModal', false)
        ->assertSet('firstName', '');
});

// ============================================
// EDIT CHILD TESTS
// ============================================

test('staff can edit a child', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Original',
        'last_name' => 'Name',
        'date_of_birth' => now()->subYears(5),
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('editChild', $child)
        ->assertSet('showEditChildModal', true)
        ->assertSet('firstName', 'Original')
        ->set('firstName', 'Updated')
        ->call('updateChild')
        ->assertHasNoErrors()
        ->assertSet('showEditChildModal', false)
        ->assertDispatched('child-updated');

    expect($child->fresh()->first_name)->toBe('Updated');
});

test('edit child validates date of birth must be under 18', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'date_of_birth' => now()->subYears(5),
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('editChild', $child)
        ->set('childDateOfBirth', now()->subYears(20)->format('Y-m-d'))
        ->call('updateChild')
        ->assertHasErrors(['childDateOfBirth']);
});

test('cancel edit child closes modal and resets form', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $child = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Original',
        'date_of_birth' => now()->subYears(5),
    ]);

    $this->actingAs($user);

    Livewire::test(ChildrenDirectory::class, ['branch' => $this->branch])
        ->call('editChild', $child)
        ->assertSet('showEditChildModal', true)
        ->set('firstName', 'Modified')
        ->call('cancelEditChild')
        ->assertSet('showEditChildModal', false)
        ->assertSet('firstName', '');
});
