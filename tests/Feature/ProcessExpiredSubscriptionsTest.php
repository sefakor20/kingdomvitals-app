<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// isActive() unit tests (no DB needed)

it('returns false for isActive when trial has expired', function (): void {
    $tenant = new Tenant([
        'status' => TenantStatus::Trial,
        'trial_ends_at' => now()->subDay(),
    ]);

    expect($tenant->isActive())->toBeFalse();
});

it('returns true for isActive when trial is still active', function (): void {
    $tenant = new Tenant([
        'status' => TenantStatus::Trial,
        'trial_ends_at' => now()->addDays(7),
    ]);

    expect($tenant->isActive())->toBeTrue();
});

it('returns true for isActive when status is active', function (): void {
    $tenant = new Tenant([
        'status' => TenantStatus::Active,
    ]);

    expect($tenant->isActive())->toBeTrue();
});

it('returns false for isActive when status is inactive', function (): void {
    $tenant = new Tenant([
        'status' => TenantStatus::Inactive,
    ]);

    expect($tenant->isActive())->toBeFalse();
});

// Command tests

it('marks cancelled tenants past subscription_ends_at as inactive', function (): void {
    $tenant = Tenant::create([
        'name' => 'Expired Church',
        'status' => TenantStatus::Active,
        'cancelled_at' => now()->subDays(10),
        'subscription_ends_at' => now()->subDays(5),
    ]);

    $this->artisan('subscriptions:process-expired')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Inactive);
});

it('does not mark cancelled tenants still in grace period as inactive', function (): void {
    $tenant = Tenant::create([
        'name' => 'Grace Period Church',
        'status' => TenantStatus::Active,
        'cancelled_at' => now()->subDays(2),
        'subscription_ends_at' => now()->addDays(5),
    ]);

    $this->artisan('subscriptions:process-expired')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

it('marks trial tenants with expired trial_ends_at as inactive', function (): void {
    $tenant = Tenant::create([
        'name' => 'Expired Trial Church',
        'status' => TenantStatus::Trial,
        'trial_ends_at' => now()->subDays(3),
    ]);

    $this->artisan('subscriptions:process-expired')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Inactive);
});

it('does not mark trial tenants with future trial_ends_at as inactive', function (): void {
    $tenant = Tenant::create([
        'name' => 'Active Trial Church',
        'status' => TenantStatus::Trial,
        'trial_ends_at' => now()->addDays(7),
    ]);

    $this->artisan('subscriptions:process-expired')->assertSuccessful();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Trial);
});
