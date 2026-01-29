<?php

use App\Livewire\Dashboard;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('dashboard component renders successfully', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test(Dashboard::class)
        ->assertStatus(200);
});

test('dashboard shows branch context', function (): void {
    $component = Livewire::actingAs(User::factory()->create())
        ->test(Dashboard::class);

    expect($component->instance())->toBeInstanceOf(Dashboard::class);
});
