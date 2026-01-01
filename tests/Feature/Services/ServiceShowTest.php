<?php

use App\Enums\BranchRole;
use App\Enums\ServiceType;
use App\Livewire\Services\ServiceShow;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
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

    // Create a test service
    $this->service = Service::factory()->create(['branch_id' => $this->branch->id]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view service show page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services/{$this->service->id}")
        ->assertOk()
        ->assertSeeLivewire(ServiceShow::class);
});

test('user without branch access cannot view service show page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services/{$this->service->id}")
        ->assertForbidden();
});

test('unauthenticated user cannot view service show page', function () {
    $this->get("/branches/{$this->branch->id}/services/{$this->service->id}")
        ->assertRedirect('/login');
});

// ============================================
// VIEW SERVICE AUTHORIZATION TESTS
// ============================================

test('admin can view service details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->service->name);
});

test('manager can view service details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->service->name);
});

test('staff can view service details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->service->name);
});

test('volunteer can view service details', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->assertSee($this->service->name);
});

// ============================================
// DATA DISPLAY TESTS
// ============================================

test('service show page displays service information correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Morning Worship',
        'day_of_week' => 0, // Sunday
        'time' => '09:30',
        'service_type' => ServiceType::Sunday,
        'capacity' => 150,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->assertSee('Sunday Morning Worship')
        ->assertSee('Sunday')
        ->assertSee('09:30')
        ->assertSee('150')
        ->assertSee('Active');
});

test('service show displays inactive status correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->inactive()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->assertSee('Inactive');
});

test('service show displays no limit when capacity is null', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'capacity' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->assertSee('No limit');
});

test('getDayName returns correct day names', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $instance = $component->instance();

    expect($instance->getDayName(0))->toBe('Sunday');
    expect($instance->getDayName(1))->toBe('Monday');
    expect($instance->getDayName(2))->toBe('Tuesday');
    expect($instance->getDayName(3))->toBe('Wednesday');
    expect($instance->getDayName(4))->toBe('Thursday');
    expect($instance->getDayName(5))->toBe('Friday');
    expect($instance->getDayName(6))->toBe('Saturday');
    expect($instance->getDayName(null))->toBe('-');
});

// ============================================
// INLINE EDITING TESTS
// ============================================

test('admin can edit service inline', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Service',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Updated Service Name')
        ->set('day_of_week', 3)
        ->set('time', '18:30')
        ->call('save')
        ->assertSet('editing', false)
        ->assertDispatched('service-updated');

    $service->refresh();
    expect($service->name)->toBe('Updated Service Name');
    expect($service->day_of_week)->toBe(3);
    expect(substr($service->time, 0, 5))->toBe('18:30'); // Database stores with seconds
});

test('manager can edit service inline', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Manager Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->name)->toBe('Manager Updated');
});

test('staff can edit service inline', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Staff Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->name)->toBe('Staff Updated');
});

test('volunteer cannot edit service', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->assertForbidden();
});

test('cancel editing resets to view mode', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $originalName = $this->service->name;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->assertSet('editing', true)
        ->set('name', 'Changed Name')
        ->call('cancel')
        ->assertSet('editing', false);

    // Verify name was not changed
    expect($this->service->fresh()->name)->toBe($originalName);
});

test('edit mode fills form with current service data', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Original Name',
        'day_of_week' => 2,
        'time' => '19:00',
        'service_type' => ServiceType::Midweek,
        'capacity' => 75,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->call('edit')
        ->assertSet('name', 'Original Name')
        ->assertSet('day_of_week', 2)
        ->assertSet('time', '19:00')
        ->assertSet('service_type', 'midweek')
        ->assertSet('capacity', 75)
        ->assertSet('is_active', true);
});

test('can toggle service active status', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->call('edit')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->is_active)->toBeFalse();
});

// ============================================
// DELETE TESTS
// ============================================

test('admin can delete service', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $serviceId = $this->service->id;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertDispatched('service-deleted')
        ->assertRedirect(route('services.index', $this->branch));

    expect(Service::find($serviceId))->toBeNull();
});

test('manager can delete service', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $serviceId = $this->service->id;

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertRedirect(route('services.index', $this->branch));

    expect(Service::find($serviceId))->toBeNull();
});

test('staff cannot delete service', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('confirmDelete')
        ->assertForbidden();
});

test('volunteer cannot delete service', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('confirmDelete')
        ->assertForbidden();
});

test('cancel delete modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('name is required when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('day_of_week is required when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('day_of_week', null)
        ->call('save')
        ->assertHasErrors(['day_of_week']);
});

test('time is required when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('time', '')
        ->call('save')
        ->assertHasErrors(['time']);
});

test('time must be valid format when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('time', 'invalid')
        ->call('save')
        ->assertHasErrors(['time']);
});

test('service type must be valid when saving', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('service_type', 'invalid_type')
        ->call('save')
        ->assertHasErrors(['service_type']);
});

test('day_of_week must be between 0 and 6', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('day_of_week', 7)
        ->call('save')
        ->assertHasErrors(['day_of_week']);
});

test('capacity must be positive if provided', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service])
        ->call('edit')
        ->set('capacity', 0)
        ->call('save')
        ->assertHasErrors(['capacity']);
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot view service from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    // Service belongs to other branch
    $service = Service::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $service])
        ->assertForbidden();
});

test('user cannot edit service from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to otherBranch but service belongs to $this->branch
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    // The mount() will fail authorization because user doesn't have access to service's branch
    Livewire::test(ServiceShow::class, ['branch' => $otherBranch, 'service' => $this->service])
        ->assertForbidden();
});

// ============================================
// COMPUTED PROPERTY TESTS
// ============================================

test('canEdit returns true for staff and above', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
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

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->canEdit)->toBeFalse();
});

test('canDelete returns true for admin and manager', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
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

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->canDelete)->toBeFalse();
});

test('serviceTypes computed property returns all service types', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    $serviceTypes = $component->instance()->serviceTypes;

    expect($serviceTypes)->toBe(ServiceType::cases());
    expect(count($serviceTypes))->toBe(6);
});

test('attendanceCount returns zero when no attendance records', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->attendanceCount)->toBe(0);
});

test('donationCount returns zero when no donations', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceShow::class, ['branch' => $this->branch, 'service' => $this->service]);
    expect($component->instance()->donationCount)->toBe(0);
});
