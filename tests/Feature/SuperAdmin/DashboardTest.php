<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use Livewire\Livewire;

it('shows tenant statistics on dashboard', function () {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Dashboard::class)
        ->assertSee('Total Tenants')
        ->assertSee('Active')
        ->assertSee('In Trial')
        ->assertSee('Suspended');
});

it('displays recent tenants widget', function () {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Dashboard::class)
        ->assertSee('Recent Tenants');
});

it('displays recent activity widget', function () {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Dashboard::class)
        ->assertSee('Recent Activity');
});

it('uses the correct layout', function () {
    $admin = SuperAdmin::factory()->create();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Dashboard::class);

    expect($component->instance()->render()->name())->toBe('livewire.super-admin.dashboard');
});
