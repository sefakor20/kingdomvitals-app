<?php

use App\Livewire\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Configure app URL and host for tenant domain routing
    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('dashboard component renders successfully', function () {
    $this->tenant->markOnboardingComplete();

    Livewire::actingAs(User::factory()->create())
        ->test(Dashboard::class)
        ->assertStatus(200);
});

test('dashboard shows branch context', function () {
    $this->tenant->markOnboardingComplete();

    $component = Livewire::actingAs(User::factory()->create())
        ->test(Dashboard::class);

    expect($component->instance())->toBeInstanceOf(Dashboard::class);
});
