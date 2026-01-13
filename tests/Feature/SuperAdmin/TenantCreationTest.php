<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantAdminInvitationNotification;
use App\Services\TenantCreationService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('creates tenant with admin user and sends invitation', function (): void {
    Notification::fake();

    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'New Church')
        ->set('domain', 'newchurch.kingdomvitals.test')
        ->set('admin_name', 'John Pastor')
        ->set('admin_email', 'pastor@newchurch.com')
        ->set('trial_days', 14)
        ->call('createTenant')
        ->assertRedirect();

    // Tenant was created
    $this->assertDatabaseHas('tenants', [
        'name' => 'New Church',
        'status' => TenantStatus::Trial->value,
    ]);

    // Domain was created
    $this->assertDatabaseHas('domains', [
        'domain' => 'newchurch.kingdomvitals.test',
    ]);

    // Admin user was created in tenant database
    $tenant = Tenant::where('name', 'New Church')->first();
    $tenant->run(function (): void {
        $user = User::where('email', 'pastor@newchurch.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('John Pastor')
            ->and($user->email_verified_at)->not->toBeNull();
    });

    // Invitation email was sent
    Notification::assertSentTo(
        [$tenant->run(fn () => User::where('email', 'pastor@newchurch.com')->first())],
        TenantAdminInvitationNotification::class
    );
});

it('validates admin name is required', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Test Church')
        ->set('domain', 'test.kingdomvitals.test')
        ->set('admin_name', '')
        ->set('admin_email', 'admin@test.com')
        ->set('trial_days', 14)
        ->call('createTenant')
        ->assertHasErrors(['admin_name']);
});

it('validates admin email is required', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Test Church')
        ->set('domain', 'test.kingdomvitals.test')
        ->set('admin_name', 'Admin Name')
        ->set('admin_email', '')
        ->set('trial_days', 14)
        ->call('createTenant')
        ->assertHasErrors(['admin_email']);
});

it('validates admin email format', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('showCreateModal', true)
        ->set('name', 'Test Church')
        ->set('domain', 'test.kingdomvitals.test')
        ->set('admin_name', 'Admin Name')
        ->set('admin_email', 'not-an-email')
        ->set('trial_days', 14)
        ->call('createTenant')
        ->assertHasErrors(['admin_email']);
});

it('resets admin fields when form is reset', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->set('admin_name', 'Test Admin')
        ->set('admin_email', 'test@admin.com')
        ->call('resetCreateForm')
        ->assertSet('admin_name', '')
        ->assertSet('admin_email', '');
});

describe('TenantCreationService', function (): void {
    it('creates tenant with admin user', function (): void {
        Notification::fake();

        $service = app(TenantCreationService::class);

        $tenant = $service->createTenantWithAdmin(
            tenantData: [
                'name' => 'Service Test Church',
                'domain' => 'servicetest.kingdomvitals.test',
                'contact_email' => 'contact@servicetest.com',
                'contact_phone' => null,
                'address' => null,
                'trial_days' => 30,
            ],
            adminData: [
                'name' => 'Service Admin',
                'email' => 'admin@servicetest.com',
            ],
        );

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->name)->toBe('Service Test Church')
            ->and($tenant->status)->toBe(TenantStatus::Trial);

        // Check domain was created
        expect($tenant->domains)->toHaveCount(1)
            ->and($tenant->domains->first()->domain)->toBe('servicetest.kingdomvitals.test');

        // Check user was created in tenant context
        $tenant->run(function (): void {
            $user = User::where('email', 'admin@servicetest.com')->first();
            expect($user)->not->toBeNull()
                ->and($user->name)->toBe('Service Admin')
                ->and($user->email_verified_at)->not->toBeNull();
        });

        // Check notification was sent
        Notification::assertSentTo(
            [$tenant->run(fn () => User::where('email', 'admin@servicetest.com')->first())],
            TenantAdminInvitationNotification::class
        );
    });

    it('generates correct reset url with tenant domain', function (): void {
        Notification::fake();

        $service = app(TenantCreationService::class);

        $tenant = $service->createTenantWithAdmin(
            tenantData: [
                'name' => 'URL Test Church',
                'domain' => 'urltest.kingdomvitals.test',
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'trial_days' => 14,
            ],
            adminData: [
                'name' => 'URL Admin',
                'email' => 'admin@urltest.com',
            ],
        );

        Notification::assertSentTo(
            [$tenant->run(fn () => User::where('email', 'admin@urltest.com')->first())],
            TenantAdminInvitationNotification::class,
            function ($notification): true {
                expect($notification->setupUrl)->toContain('urltest.kingdomvitals.test')
                    ->and($notification->setupUrl)->toContain('reset-password')
                    ->and($notification->setupUrl)->toContain('email=');

                return true;
            }
        );
    });
});
