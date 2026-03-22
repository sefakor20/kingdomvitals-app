<?php

declare(strict_types=1);

use App\Livewire\SuperAdmin\Dashboard;
use App\Models\SuperAdmin;
use Livewire\Livewire;

it('shows tenant statistics on dashboard', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(Dashboard::class)
        ->assertSee('Total Tenants')
        ->assertSee('Active')
        ->assertSee('In Trial')
        ->assertSee('Suspended');
});

it('displays recent tenants widget', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(Dashboard::class)
        ->assertSee('Recent Tenants');
});

it('displays recent activity widget', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(Dashboard::class)
        ->assertSee('Recent Activity');
});

it('uses the correct layout', function (): void {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(Dashboard::class);

    expect($component->instance()->render()->name())->toBe('livewire.super-admin.dashboard');
});
