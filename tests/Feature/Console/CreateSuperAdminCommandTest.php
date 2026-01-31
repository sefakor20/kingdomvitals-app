<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;

it('creates a super admin with default owner role', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'test@example.com',
        '--name' => 'Test Admin',
    ])
        ->assertExitCode(0)
        ->expectsOutput('Super admin created successfully!');

    $this->assertDatabaseHas('super_admins', [
        'email' => 'test@example.com',
        'name' => 'Test Admin',
        'role' => 'owner',
        'is_active' => true,
    ]);
});

it('creates a super admin with specific role', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'admin@example.com',
        '--role' => 'admin',
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('super_admins', [
        'email' => 'admin@example.com',
        'role' => 'admin',
    ]);
});

it('skips creation if email already exists', function (): void {
    SuperAdmin::factory()->create(['email' => 'existing@example.com']);

    $this->artisan('superadmin:create', [
        'email' => 'existing@example.com',
    ])
        ->assertExitCode(0)
        ->expectsOutput("Super admin with email 'existing@example.com' already exists. Skipping.");

    expect(SuperAdmin::where('email', 'existing@example.com')->count())->toBe(1);
});

it('uses email prefix as name when not provided', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'john.doe@example.com',
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('super_admins', [
        'email' => 'john.doe@example.com',
        'name' => 'john.doe',
    ]);
});

it('uses provided password when specified', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'secure@example.com',
        '--password' => 'MySecurePassword123!',
    ])
        ->assertExitCode(0);

    $admin = SuperAdmin::where('email', 'secure@example.com')->first();
    expect(Hash::check('MySecurePassword123!', $admin->password))->toBeTrue();
});

it('creates support role super admin', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'support@example.com',
        '--role' => 'support',
    ])
        ->assertExitCode(0);

    $this->assertDatabaseHas('super_admins', [
        'email' => 'support@example.com',
        'role' => 'support',
    ]);
});

it('sets email as verified', function (): void {
    $this->artisan('superadmin:create', [
        'email' => 'verified@example.com',
    ])
        ->assertExitCode(0);

    $admin = SuperAdmin::where('email', 'verified@example.com')->first();
    expect($admin->email_verified_at)->not->toBeNull();
});
