<?php

declare(strict_types=1);

use App\Livewire\SuperAdmin\Profile\Appearance;
use App\Livewire\SuperAdmin\Profile\Password;
use App\Livewire\SuperAdmin\Profile\Profile;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the profile edit page with prefilled name and email', function (): void {
    $admin = SuperAdmin::factory()->create([
        'name' => 'Raphael Adinkrah',
        'email' => 'raphael@example.com',
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(Profile::class)
        ->assertOk()
        ->assertSet('name', 'Raphael Adinkrah')
        ->assertSet('email', 'raphael@example.com');
});

it('updates the super admin name and email', function (): void {
    $admin = SuperAdmin::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(Profile::class)
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertDispatched('profile-updated');

    expect($admin->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.com');
});

it('rejects duplicate emails on profile update', function (): void {
    SuperAdmin::factory()->create(['email' => 'taken@example.com']);
    $admin = SuperAdmin::factory()->create(['email' => 'me@example.com']);

    Livewire::actingAs($admin, 'superadmin')
        ->test(Profile::class)
        ->set('email', 'taken@example.com')
        ->call('updateProfileInformation')
        ->assertHasErrors(['email']);
});

it('renders the password page', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(Password::class)
        ->assertOk();
});

it('updates the password when the current password is correct', function (): void {
    $admin = SuperAdmin::factory()->create([
        'password' => Hash::make('Old-Password-123!'),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(Password::class)
        ->set('current_password', 'Old-Password-123!')
        ->set('password', 'New-Password-456!')
        ->set('password_confirmation', 'New-Password-456!')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertDispatched('password-updated');

    expect(Hash::check('New-Password-456!', $admin->fresh()->password))->toBeTrue();
});

it('rejects password update when current password is wrong', function (): void {
    $admin = SuperAdmin::factory()->create([
        'password' => Hash::make('Old-Password-123!'),
    ]);

    Livewire::actingAs($admin, 'superadmin')
        ->test(Password::class)
        ->set('current_password', 'WRONG')
        ->set('password', 'New-Password-456!')
        ->set('password_confirmation', 'New-Password-456!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('Old-Password-123!', $admin->fresh()->password))->toBeTrue();
});

it('renders the appearance page', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(Appearance::class)
        ->assertOk()
        ->assertSee('Appearance');
});
