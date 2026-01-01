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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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

// ============================================
// INLINE EDITING TESTS
// ============================================

test('admin can enter edit mode', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->assertSet('editing', false)
        ->call('edit')
        ->assertSet('editing', true)
        ->assertSet('first_name', 'John')
        ->assertSet('last_name', 'Doe');
});

test('staff can enter edit mode', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertSet('editing', true);
});

test('volunteer cannot enter edit mode', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertForbidden();
});

test('can save updated member data', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('email', 'jane.smith@example.com')
        ->call('save')
        ->assertSet('editing', false)
        ->assertDispatched('member-updated');

    $this->member->refresh();
    expect($this->member->first_name)->toBe('Jane');
    expect($this->member->last_name)->toBe('Smith');
    expect($this->member->email)->toBe('jane.smith@example.com');
});

test('can cancel editing without saving changes', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('first_name', 'Jane')
        ->call('cancel')
        ->assertSet('editing', false);

    $this->member->refresh();
    expect($this->member->first_name)->toBe('John');
});

test('validation errors are shown when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('first_name', '')
        ->set('last_name', '')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name']);
});

test('email must be valid format when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('can update all member fields', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('first_name', 'Updated')
        ->set('last_name', 'Member')
        ->set('middle_name', 'New')
        ->set('email', 'updated@example.com')
        ->set('phone', '0551234567')
        ->set('date_of_birth', '1985-03-20')
        ->set('gender', 'female')
        ->set('marital_status', 'single')
        ->set('status', 'inactive')
        ->set('address', '456 New Street')
        ->set('city', 'Kumasi')
        ->set('state', 'Ashanti')
        ->set('zip', '00000')
        ->set('country', 'Ghana')
        ->set('joined_at', '2023-06-01')
        ->set('baptized_at', '2023-12-25')
        ->set('notes', 'Updated notes')
        ->call('save')
        ->assertSet('editing', false);

    $this->member->refresh();
    expect($this->member->first_name)->toBe('Updated');
    expect($this->member->last_name)->toBe('Member');
    expect($this->member->middle_name)->toBe('New');
    expect($this->member->email)->toBe('updated@example.com');
    expect($this->member->phone)->toBe('0551234567');
    expect($this->member->gender)->toBe(Gender::Female);
    expect($this->member->marital_status)->toBe(MaritalStatus::Single);
    expect($this->member->status)->toBe(MembershipStatus::Inactive);
    expect($this->member->address)->toBe('456 New Street');
    expect($this->member->city)->toBe('Kumasi');
    expect($this->member->notes)->toBe('Updated notes');
});

test('volunteer cannot save member data', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    // Manually set editing to true to bypass edit() authorization
    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->set('editing', true)
        ->set('first_name', 'Hacker')
        ->call('save')
        ->assertForbidden();
});

test('edit mode populates all form fields correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertSet('first_name', 'John')
        ->assertSet('last_name', 'Doe')
        ->assertSet('middle_name', 'Michael')
        ->assertSet('email', 'john.doe@example.com')
        ->assertSet('phone', '0241234567')
        ->assertSet('date_of_birth', '1990-05-15')
        ->assertSet('gender', 'male')
        ->assertSet('marital_status', 'married')
        ->assertSet('status', 'active')
        ->assertSet('address', '123 Main Street')
        ->assertSet('city', 'Accra')
        ->assertSet('state', 'Greater Accra')
        ->assertSet('zip', '00233')
        ->assertSet('country', 'Ghana')
        ->assertSet('notes', 'A very active member of the congregation.');
});

// ============================================
// PHOTO EDITING TESTS
// ============================================

test('can upload photo in edit mode', function () {
    Storage::fake('livewire-tmp');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('photo', $photo)
        ->call('save')
        ->assertSet('editing', false);

    $this->member->refresh();
    expect($this->member->photo_url)->not->toBeNull();
    expect($this->member->photo_url)->toContain('/storage/members/');
});

test('can replace existing photo in edit mode', function () {
    Storage::fake('livewire-tmp');

    // Set up member with existing photo
    $this->member->update(['photo_url' => '/storage/members/test/old-photo.jpg']);

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $newPhoto = UploadedFile::fake()->image('new-avatar.jpg', 100, 100);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertSet('existingPhotoUrl', '/storage/members/test/old-photo.jpg')
        ->set('photo', $newPhoto)
        ->call('save')
        ->assertSet('editing', false);

    $this->member->refresh();
    expect($this->member->photo_url)->not->toBeNull();
    expect($this->member->photo_url)->not->toBe('/storage/members/test/old-photo.jpg');
});

test('can remove photo in edit mode', function () {
    // Set up member with existing photo
    $this->member->update(['photo_url' => '/storage/members/test/photo.jpg']);

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertSet('existingPhotoUrl', '/storage/members/test/photo.jpg')
        ->call('removePhoto')
        ->assertSet('existingPhotoUrl', null)
        ->assertSet('photo', null);

    $this->member->refresh();
    expect($this->member->photo_url)->toBeNull();
});

test('photo must be an image in edit mode', function () {
    Storage::fake('livewire-tmp');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $textFile = UploadedFile::fake()->create('document.txt', 100);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('photo', $textFile)
        ->call('save')
        ->assertHasErrors(['photo']);
});

test('photo must not exceed 2mb in edit mode', function () {
    Storage::fake('livewire-tmp');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $largePhoto = UploadedFile::fake()->image('large.jpg')->size(3000);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('photo', $largePhoto)
        ->call('save')
        ->assertHasErrors(['photo']);
});

test('edit mode sets existing photo url', function () {
    $this->member->update(['photo_url' => '/storage/members/test/photo.jpg']);

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->assertSet('existingPhotoUrl', '/storage/members/test/photo.jpg')
        ->assertSet('photo', null);
});

test('cancel resets photo fields', function () {
    Storage::fake('livewire-tmp');

    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    Livewire::test(MemberShow::class, ['branch' => $this->branch, 'member' => $this->member])
        ->call('edit')
        ->set('photo', $photo)
        ->call('cancel')
        ->assertSet('photo', null)
        ->assertSet('existingPhotoUrl', null)
        ->assertSet('editing', false);
});
