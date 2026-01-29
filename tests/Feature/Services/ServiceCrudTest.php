<?php

use App\Enums\BranchRole;
use App\Enums\ServiceType;
use App\Livewire\Services\ServiceIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
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
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view services page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services")
        ->assertOk()
        ->assertSeeLivewire(ServiceIndex::class);
});

test('user without branch access cannot view services page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    // User has access to other branch, not this one
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/services")
        ->assertForbidden();
});

test('unauthenticated user cannot view services page', function (): void {
    $this->get("/branches/{$this->branch->id}/services")
        ->assertRedirect('/login');
});

// ============================================
// VIEW SERVICES AUTHORIZATION TESTS
// ============================================

test('admin can view services list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee($service->name);
});

test('manager can view services list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee($service->name);
});

test('staff can view services list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee($service->name);
});

test('volunteer can view services list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee($service->name);
});

// ============================================
// CREATE SERVICE AUTHORIZATION TESTS
// ============================================

test('admin can create a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Sunday Morning Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('service-created');

    expect(Service::where('name', 'Sunday Morning Service')->exists())->toBeTrue();
});

test('manager can create a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Manager Service')
        ->set('day_of_week', 3)
        ->set('time', '18:00')
        ->set('service_type', 'midweek')
        ->call('store')
        ->assertHasNoErrors();

    expect(Service::where('name', 'Manager Service')->exists())->toBeTrue();
});

test('staff can create a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Staff Service')
        ->set('day_of_week', 5)
        ->set('time', '19:00')
        ->set('service_type', 'prayer')
        ->call('store')
        ->assertHasNoErrors();

    expect(Service::where('name', 'Staff Service')->exists())->toBeTrue();
});

test('volunteer cannot create a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE SERVICE AUTHORIZATION TESTS
// ============================================

test('admin can update a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertSet('showEditModal', true)
        ->set('name', 'Updated Service')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('service-updated');

    expect($service->fresh()->name)->toBe('Updated Service');
});

test('manager can update a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertSet('showEditModal', true)
        ->set('name', 'Manager Updated')
        ->call('update')
        ->assertHasNoErrors();

    expect($service->fresh()->name)->toBe('Manager Updated');
});

test('staff can update a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertSet('showEditModal', true)
        ->set('name', 'Staff Updated')
        ->call('update')
        ->assertHasNoErrors();

    expect($service->fresh()->name)->toBe('Staff Updated');
});

test('volunteer cannot update a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertForbidden();
});

// ============================================
// DELETE SERVICE AUTHORIZATION TESTS
// ============================================

test('admin can delete a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $serviceId = $service->id;

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('service-deleted');

    expect(Service::find($serviceId))->toBeNull();
});

test('manager can delete a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);
    $serviceId = $service->id;

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Service::find($serviceId))->toBeNull();
});

test('staff cannot delete a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertForbidden();
});

test('volunteer cannot delete a service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search services by name', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Morning',
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Wednesday Prayer',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->set('search', 'Sunday')
        ->assertSee('Sunday Morning')
        ->assertDontSee('Wednesday Prayer');
});

test('can filter services by type', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Service::factory()->sunday()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Service',
    ]);

    Service::factory()->prayer()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Prayer Meeting',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', 'sunday')
        ->assertSee('Sunday Service')
        ->assertDontSee('Prayer Meeting');
});

test('can filter services by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Active Service',
        'is_active' => true,
    ]);

    Service::factory()->inactive()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Inactive Service',
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active')
        ->assertSee('Active Service')
        ->assertDontSee('Inactive Service');
});

test('empty search shows all services', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $services = Service::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->set('search', '');

    foreach ($services as $service) {
        $component->assertSee($service->name);
    }
});

// ============================================
// VALIDATION TESTS
// ============================================

test('name is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', '')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasErrors(['name']);
});

test('day of week is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week')
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasErrors(['day_of_week']);
});

test('time is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week', 0)
        ->set('time', '')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasErrors(['time']);
});

test('service type is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', '')
        ->call('store')
        ->assertHasErrors(['service_type']);
});

test('service type must be valid value', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'invalid_type')
        ->call('store')
        ->assertHasErrors(['service_type']);
});

test('day of week must be between 0 and 6', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week', 7)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasErrors(['day_of_week']);
});

test('capacity must be positive if provided', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Test Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->set('capacity', 0)
        ->call('store')
        ->assertHasErrors(['capacity']);
});

test('can create service with all optional fields empty', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Minimal Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->call('store')
        ->assertHasNoErrors();

    $service = Service::where('name', 'Minimal Service')->first();
    expect($service)->not->toBeNull();
    expect($service->capacity)->toBeNull();
});

test('can create service with all fields filled', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('name', 'Complete Service')
        ->set('day_of_week', 0)
        ->set('time', '09:00')
        ->set('service_type', 'sunday')
        ->set('capacity', 500)
        ->set('is_active', true)
        ->call('store')
        ->assertHasNoErrors();

    $service = Service::where('name', 'Complete Service')->first();
    expect($service)->not->toBeNull();
    expect($service->day_of_week)->toBe(0);
    expect(substr($service->time, 0, 5))->toBe('09:00'); // Database stores with seconds
    expect($service->capacity)->toBe(500);
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

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('name', 'Test Service')
        ->set('service_type', 'sunday')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('name', '')
        ->assertSet('service_type', '');
});

test('cancel edit modal closes modal and clears editing service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertSet('showEditModal', true)
        ->assertSet('editingService.id', $service->id)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingService', null);
});

test('cancel delete modal closes modal and clears deleting service', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $service = Service::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertSet('showDeleteModal', true)
        ->assertSet('deletingService.id', $service->id)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingService', null);
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update service from different branch', function (): void {
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

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('edit', $service)
        ->assertForbidden();
});

test('user cannot delete service from different branch', function (): void {
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

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $service)
        ->assertForbidden();
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no services exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee('No services found');
});

test('service table displays service information correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $service = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Service',
        'day_of_week' => 0,
        'time' => '09:00',
        'service_type' => ServiceType::Sunday,
        'capacity' => 300,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee('Test Service')
        ->assertSee('Sunday')
        ->assertSee('09:00')
        ->assertSee('300')
        ->assertSee('Active');
});

test('day name is displayed correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Wednesday Service',
        'day_of_week' => 3,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee('Wednesday');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ServiceIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Service');
});

test('create button is hidden for volunteers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceIndex::class, ['branch' => $this->branch]);

    expect($component->instance()->canCreate)->toBeFalse();

    $component->call('create')->assertForbidden();
});

// ============================================
// SERVICE ORDERING TESTS
// ============================================

test('services are ordered by day of week then time', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    // Create services in random order
    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Wednesday Evening',
        'day_of_week' => 3,
        'time' => '19:00',
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Morning',
        'day_of_week' => 0,
        'time' => '09:00',
    ]);

    Service::factory()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Sunday Evening',
        'day_of_week' => 0,
        'time' => '18:00',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ServiceIndex::class, ['branch' => $this->branch]);
    $services = $component->instance()->services;

    // Should be ordered: Sunday Morning (0, 09:00), Sunday Evening (0, 18:00), Wednesday Evening (3, 19:00)
    expect($services[0]->name)->toBe('Sunday Morning');
    expect($services[1]->name)->toBe('Sunday Evening');
    expect($services[2]->name)->toBe('Wednesday Evening');
});
