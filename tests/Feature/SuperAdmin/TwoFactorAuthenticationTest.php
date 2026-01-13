<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

it('can view security settings page', function (): void {
    $admin = SuperAdmin::factory()->create();

    $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.profile.security'))
        ->assertOk()
        ->assertSee('Security Settings');
});

it('shows 2FA as disabled by default', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->assertSet('twoFactorEnabled', false)
        ->assertSee('Disabled');
});

it('can enable 2FA and show QR code modal', function (): void {
    $admin = SuperAdmin::factory()->create();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->assertSet('showModal', false)
        ->call('enable')
        ->assertSet('showModal', true);

    // Verify that two_factor_secret was set
    $admin->refresh();
    expect($admin->two_factor_secret)->not->toBeNull();
});

it('can confirm 2FA with valid code', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable 2FA first (which generates the secret)
    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);

    // Get a valid TOTP code using Google2FA
    $admin->refresh();
    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp(decrypt($admin->two_factor_secret));

    $component
        ->set('code', $validCode)
        ->call('confirmTwoFactor')
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showModal', false)
        ->assertDispatched('two-factor-enabled');

    $admin->refresh();
    expect($admin->two_factor_confirmed_at)->not->toBeNull();
});

it('can disable 2FA', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA first
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->assertSet('twoFactorEnabled', true)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false)
        ->assertDispatched('two-factor-disabled');

    $admin->refresh();
    expect($admin->two_factor_secret)->toBeNull();
});

it('shows recovery codes when 2FA is enabled', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['ABCD-EFGH-IJKL', 'MNOP-QRST-UVWX'])),
    ])->save();

    Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->assertSet('twoFactorEnabled', true)
        ->assertSee('Recovery Codes')
        ->assertSee('ABCD-EFGH-IJKL');
});

it('can regenerate recovery codes', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['OLD-CODE-1', 'OLD-CODE-2'])),
    ])->save();

    $component = Livewire::actingAs($admin, 'superadmin')
        ->test(\App\Livewire\SuperAdmin\Profile\Security::class)
        ->assertSee('OLD-CODE-1')
        ->call('regenerateRecoveryCodes')
        ->assertDispatched('recovery-codes-regenerated');

    $admin->refresh();
    $newCodes = json_decode(decrypt($admin->two_factor_recovery_codes), true);
    expect($newCodes)->not->toContain('OLD-CODE-1');
});

it('redirects to 2FA challenge when logging in with 2FA enabled', function (): void {
    $admin = SuperAdmin::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    $this->post(route('superadmin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.two-factor.challenge'));

    // Should not be fully logged in yet
    expect(auth('superadmin')->check())->toBeFalse();
});

it('can view 2FA challenge page', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Simulate the session state after password verification
    $this->withSession([
        'superadmin.login.id' => $admin->id,
        'superadmin.login.remember' => false,
    ])->get(route('superadmin.two-factor.challenge'))
        ->assertOk()
        ->assertSee('Authentication Code');
});

it('redirects to login if no session data on 2FA challenge', function (): void {
    $this->get(route('superadmin.two-factor.challenge'))
        ->assertRedirect(route('superadmin.login'));
});

it('can complete 2FA challenge with valid code', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    $validCode = $google2fa->getCurrentOtp($secret);

    $this->withSession([
        'superadmin.login.id' => $admin->id,
        'superadmin.login.remember' => false,
    ])->post(route('superadmin.two-factor.challenge'), [
        'code' => $validCode,
    ])->assertRedirect(route('superadmin.dashboard'));

    // Should be fully logged in now
    expect(auth('superadmin')->check())->toBeTrue();
});

it('can complete 2FA challenge with recovery code', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA with specific recovery codes
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['ABCD-EFGH-IJKL', 'MNOP-QRST-UVWX'])),
    ])->save();

    $this->withSession([
        'superadmin.login.id' => $admin->id,
        'superadmin.login.remember' => false,
    ])->post(route('superadmin.two-factor.challenge'), [
        'recovery_code' => 'ABCD-EFGH-IJKL',
    ])->assertRedirect(route('superadmin.dashboard'));

    // Should be logged in
    expect(auth('superadmin')->check())->toBeTrue();

    // Recovery code should be consumed
    $admin->refresh();
    $remainingCodes = json_decode(decrypt($admin->two_factor_recovery_codes), true);
    expect($remainingCodes)->not->toContain('ABCD-EFGH-IJKL');
});

it('rejects invalid 2FA code', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $admin->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    $this->withSession([
        'superadmin.login.id' => $admin->id,
        'superadmin.login.remember' => false,
    ])->post(route('superadmin.two-factor.challenge'), [
        'code' => '000000',
    ])->assertSessionHasErrors('code');

    expect(auth('superadmin')->check())->toBeFalse();
});

it('logs in without 2FA redirect when 2FA not enabled', function (): void {
    $admin = SuperAdmin::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->post(route('superadmin.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('superadmin.dashboard'));

    expect(auth('superadmin')->check())->toBeTrue();
});
