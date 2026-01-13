<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;

beforeEach(function (): void {
    // Super admin tests use the landlord database
});

it('creates a super admin with valid factory', function (): void {
    $admin = SuperAdmin::factory()->create();

    expect($admin->id)->not->toBeNull();
    expect($admin->email)->toBeString();
    expect($admin->is_active)->toBeTrue();
});

it('creates owner super admin', function (): void {
    $admin = SuperAdmin::factory()->owner()->create();

    expect($admin->role->value)->toBe('owner');
    expect($admin->hasFullAccess())->toBeTrue();
});

it('creates support super admin', function (): void {
    $admin = SuperAdmin::factory()->support()->create();

    expect($admin->role->value)->toBe('support');
    expect($admin->hasFullAccess())->toBeFalse();
});

it('creates inactive super admin', function (): void {
    $admin = SuperAdmin::factory()->inactive()->create();

    expect($admin->is_active)->toBeFalse();
});

it('creates locked super admin', function (): void {
    $admin = SuperAdmin::factory()->locked()->create();

    expect($admin->isLocked())->toBeTrue();
    expect($admin->failed_login_attempts)->toBe(3);
});

it('can record successful login', function (): void {
    $admin = SuperAdmin::factory()->create([
        'failed_login_attempts' => 2,
        'last_login_at' => null,
    ]);

    $admin->recordLogin('192.168.1.1');

    expect($admin->last_login_at)->not->toBeNull();
    expect($admin->last_login_ip)->toBe('192.168.1.1');
    expect($admin->failed_login_attempts)->toBe(0);
    expect($admin->locked_until)->toBeNull();
});

it('can record failed login attempt', function (): void {
    $admin = SuperAdmin::factory()->create([
        'failed_login_attempts' => 0,
    ]);

    $admin->recordFailedLogin();

    expect($admin->failed_login_attempts)->toBe(1);
});

it('locks account after max failed attempts', function (): void {
    $admin = SuperAdmin::factory()->create([
        'failed_login_attempts' => 2,
    ]);

    $admin->recordFailedLogin();

    expect($admin->failed_login_attempts)->toBe(3);
    expect($admin->isLocked())->toBeTrue();
});

it('logs activity on successful authentication', function (): void {
    $admin = SuperAdmin::factory()->create();

    SuperAdminActivityLog::log(
        superAdmin: $admin,
        action: 'login',
        description: 'Super admin logged in',
    );

    expect(SuperAdminActivityLog::where('super_admin_id', $admin->id)->count())->toBe(1);
    expect(SuperAdminActivityLog::where('action', 'login')->exists())->toBeTrue();
});

it('super admin has correct role methods', function (): void {
    $owner = SuperAdmin::factory()->owner()->create();
    $admin = SuperAdmin::factory()->create(); // Default is admin role
    $support = SuperAdmin::factory()->support()->create();

    expect($owner->isOwner())->toBeTrue();
    expect($owner->hasFullAccess())->toBeTrue();

    expect($admin->isOwner())->toBeFalse();
    expect($admin->hasFullAccess())->toBeTrue();

    expect($support->isOwner())->toBeFalse();
    expect($support->hasFullAccess())->toBeFalse();
});

it('can filter active super admins', function (): void {
    SuperAdmin::factory()->count(3)->create(['is_active' => true]);
    SuperAdmin::factory()->count(2)->inactive()->create();

    $activeCount = SuperAdmin::active()->count();

    expect($activeCount)->toBe(3);
});

it('can filter super admins by role', function (): void {
    SuperAdmin::factory()->owner()->create();
    SuperAdmin::factory()->count(2)->create(); // admin role
    SuperAdmin::factory()->support()->create();

    $adminCount = SuperAdmin::withRole(\App\Enums\SuperAdminRole::Admin)->count();

    expect($adminCount)->toBe(2);
});
