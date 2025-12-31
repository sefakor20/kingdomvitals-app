<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

test('returns a successful response', function () {
    // Root route redirects to dashboard in tenant context
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
})->skip('Requires tenant domain routing setup');
