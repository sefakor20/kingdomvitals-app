<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('can view tenants list', function (): void {
    $admin = SuperAdmin::factory()->create();

    $response = $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.tenants.index'));

    $response->assertOk();
    $response->assertSee('Tenants');
});

it('displays tenants in the list', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'test-church-123',
        'name' => 'Test Church',
        'status' => TenantStatus::Active,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->assertSee('Test Church');
});

it('can search tenants', function (): void {
    $admin = SuperAdmin::factory()->create();
    Tenant::create([
        'id' => 'searchable-church-123',
        'name' => 'Searchable Church',
        'status' => TenantStatus::Active,
    ]);
    Tenant::create([
        'id' => 'other-church-456',
        'name' => 'Other Church',
        'status' => TenantStatus::Active,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('search', 'Searchable')
        ->assertSee('Searchable Church')
        ->assertDontSee('Other Church');
});

it('can filter tenants by status', function (): void {
    $admin = SuperAdmin::factory()->create();
    Tenant::create([
        'id' => 'active-church-123',
        'name' => 'Active Church',
        'status' => TenantStatus::Active,
    ]);
    Tenant::create([
        'id' => 'trial-church-456',
        'name' => 'Trial Church',
        'status' => TenantStatus::Trial,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('status', TenantStatus::Active->value)
        ->assertSee('Active Church')
        ->assertDontSee('Trial Church');
});

it('can open tenant create modal', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->assertSet('showCreateModal', false)
        ->set('showCreateModal', true)
        ->assertSet('showCreateModal', true);
});

it('can create a new tenant', function (): void {
    Notification::fake();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'New Test Church')
        ->set('domain', 'newtest.kingdomvitals.test')
        ->set('contact_email', 'contact@newtest.com')
        ->set('admin_name', 'Test Admin')
        ->set('admin_email', 'admin@newtest.com')
        ->set('trial_days', 14)
        ->call('createTenant')
        ->assertRedirect();

    $this->assertDatabaseHas('tenants', [
        'name' => 'New Test Church',
        'status' => TenantStatus::Trial->value,
    ]);

    $this->assertDatabaseHas('domains', [
        'domain' => 'newtest.kingdomvitals.test',
    ]);
});

it('validates required fields when creating tenant', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', '')
        ->set('domain', '')
        ->set('admin_name', '')
        ->set('admin_email', '')
        ->call('createTenant')
        ->assertHasErrors(['name', 'domain', 'admin_name', 'admin_email']);
});

it('can view tenant details', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'view-church-123',
        'name' => 'View Church',
        'status' => TenantStatus::Active,
        'contact_email' => 'contact@viewchurch.com',
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->assertSee('View Church')
        ->assertSee('contact@viewchurch.com');
});

it('can suspend a tenant', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'suspend-church-123',
        'name' => 'Suspend Church',
        'status' => TenantStatus::Active,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->set('showSuspendModal', true)
        ->set('suspensionReason', 'Non-payment of fees')
        ->call('suspend');

    $tenant->refresh();
    expect($tenant->status)->toBe(TenantStatus::Suspended);
    expect($tenant->suspension_reason)->toBe('Non-payment of fees');
});

it('can reactivate a suspended tenant', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'reactivate-church-123',
        'name' => 'Reactivate Church',
        'status' => TenantStatus::Suspended,
        'suspension_reason' => 'Test suspension',
        'suspended_at' => now(),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->call('reactivate');

    $tenant->refresh();
    expect($tenant->status)->toBe(TenantStatus::Active);
    expect($tenant->suspension_reason)->toBeNull();
});

it('can change tenant status', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'status-church-123',
        'name' => 'Status Church',
        'status' => TenantStatus::Trial,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->call('updateStatus', TenantStatus::Active->value);

    $tenant->refresh();
    expect($tenant->status)->toBe(TenantStatus::Active);
});

it('logs tenant creation activity', function (): void {
    Notification::fake();
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Logged Church')
        ->set('domain', 'logged.kingdomvitals.test')
        ->set('admin_name', 'Logged Admin')
        ->set('admin_email', 'admin@logged.com')
        ->set('trial_days', 14)
        ->call('createTenant');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'tenant_created',
    ]);
});

it('can open tenant edit modal', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'edit-modal-church-123',
        'name' => 'Edit Modal Church',
        'status' => TenantStatus::Active,
        'contact_email' => 'edit@church.com',
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->assertSet('showEditModal', false)
        ->call('openEditModal')
        ->assertSet('showEditModal', true)
        ->assertSet('editName', 'Edit Modal Church')
        ->assertSet('editContactEmail', 'edit@church.com');
});

it('can update a tenant', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'update-church-123',
        'name' => 'Original Church',
        'status' => TenantStatus::Active,
        'contact_email' => 'original@church.com',
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->call('openEditModal')
        ->set('editName', 'Updated Church')
        ->set('editContactEmail', 'updated@church.com')
        ->call('updateTenant')
        ->assertSet('showEditModal', false);

    $tenant->refresh();
    expect($tenant->name)->toBe('Updated Church');
    expect($tenant->contact_email)->toBe('updated@church.com');
});

it('logs tenant update activity', function (): void {
    $admin = SuperAdmin::factory()->create();
    $tenant = Tenant::create([
        'id' => 'log-update-church-123',
        'name' => 'Log Update Church',
        'status' => TenantStatus::Active,
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantShow::class, ['tenant' => $tenant])
        ->call('openEditModal')
        ->set('editName', 'Log Update Church - Updated')
        ->call('updateTenant');

    $this->assertDatabaseHas('super_admin_activity_logs', [
        'super_admin_id' => $admin->id,
        'action' => 'tenant_updated',
        'tenant_id' => $tenant->id,
    ]);
});
