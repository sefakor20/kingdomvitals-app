<?php

use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

test('command seeds demo data for a tenant by ID', function (): void {
    // End tenancy context before running command (command manages its own tenancy)
    tenancy()->end();

    // Run the command for this specific tenant
    $this->artisan('tenant:seed-demo', ['tenant' => $this->tenant->id])
        ->assertSuccessful();

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    // Verify data was created
    expect(Branch::count())->toBeGreaterThan(0);
    expect(Member::count())->toBeGreaterThan(0);
    expect(Service::count())->toBeGreaterThan(0);
});

test('command seeds demo data for a tenant by domain', function (): void {
    // Verify no data exists initially
    expect(Member::count())->toBe(0);

    // Run the command using domain
    $this->artisan('tenant:seed-demo', ['tenant' => 'test.localhost'])
        ->expectsOutputToContain('Demo data seeding completed!')
        ->assertSuccessful();

    // Re-initialize tenancy
    tenancy()->initialize($this->tenant);

    // Verify data was created
    expect(Member::count())->toBeGreaterThan(0);
});

test('command seeds only specified modules', function (): void {
    // Run the command with specific modules
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['members', 'households'],
    ])->assertSuccessful();

    // Re-initialize tenancy
    tenancy()->initialize($this->tenant);

    // Verify specified modules have data
    expect(Member::count())->toBeGreaterThan(0);
    expect(Household::count())->toBeGreaterThan(0);

    // Verify unspecified modules don't have data
    expect(Visitor::count())->toBe(0);
    expect(Donation::count())->toBe(0);
});

test('command uses count multiplier', function (): void {
    // Run with count=1 (default)
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['clusters'],
    ])->assertSuccessful();

    tenancy()->initialize($this->tenant);
    $baseCount = Cluster::count();
    $this->truncateTenantTables();

    // Run with count=2
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['clusters'],
        '--count' => 2,
    ])->assertSuccessful();

    tenancy()->initialize($this->tenant);
    $doubledCount = Cluster::count();

    // Count should be approximately doubled
    expect($doubledCount)->toBeGreaterThanOrEqual($baseCount * 2 - 1);
});

test('command can skip enabling modules', function (): void {
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['members'],
        '--skip-enable-modules' => true,
    ])
        ->doesntExpectOutputToContain('All modules enabled')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);
    expect(Member::count())->toBeGreaterThan(0);
});

test('command uses existing main branch', function (): void {
    // Create a main branch first
    $existingBranch = Branch::factory()->main()->create(['name' => 'Existing Main Campus']);

    // Run the command
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['members'],
    ])
        ->expectsOutputToContain('Using existing main branch')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    // Should only have one branch (the existing one)
    expect(Branch::count())->toBe(1);
    expect(Branch::first()->name)->toBe('Existing Main Campus');
});

test('command fails gracefully for invalid tenant', function (): void {
    $this->artisan('tenant:seed-demo', ['tenant' => 'invalid-tenant-id'])
        ->expectsOutputToContain('Tenant not found')
        ->assertFailed();
});

test('command enables all modules for tenant subscription plan', function (): void {
    $this->artisan('tenant:seed-demo', [
        'tenant' => $this->tenant->id,
        '--modules' => ['members'],
    ])
        ->expectsOutputToContain('All modules enabled')
        ->assertSuccessful();

    // Verify subscription plan has all modules enabled
    $this->tenant->refresh();
    expect($this->tenant->subscriptionPlan->enabled_modules)->toBeNull();
});
