<?php

declare(strict_types=1);

use App\Livewire\Settings\TwoFactor;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    // Load Fortify routes first
    require base_path('vendor/laravel/fortify/routes/routes.php');

    // Then load tenant and web routes
    Route::middleware(['web'])->group(base_path('routes/tenant.php'));
    Route::middleware(['web'])->group(base_path('routes/web.php'));

    // Create branch and mark onboarding as complete for HTTP tests
    $this->branch = Branch::factory()->main()->create();
    $this->tenant->setOnboardingData([
        'completed' => true,
        'completed_at' => now()->toISOString(),
        'branch_id' => $this->branch->id,
    ]);

    Cache::flush();

    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// LIVEWIRE COMPONENT TESTS
// ============================================

test('two factor authentication disabled when confirmation abandoned between requests', function (): void {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    $component = Livewire::test(TwoFactor::class);

    $component->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

// ============================================
// ENABLE 2FA TESTS
// ============================================

test('shows 2FA as disabled by default', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false)
        ->assertSee('Disabled');
});

test('can enable 2FA and show QR code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->assertSet('showModal', false)
        ->call('enable')
        ->assertSet('showModal', true);

    // Verify that two_factor_secret was set
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
});

test('can confirm 2FA with valid code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    // Enable 2FA first (which generates the secret)
    $component = Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);

    // Get a valid TOTP code using Google2FA
    $user->refresh();
    $google2fa = new Google2FA;
    $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

    $component
        ->set('code', $validCode)
        ->call('confirmTwoFactor')
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showModal', false);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
});

test('rejects invalid 2FA code during setup', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    // Enable 2FA first (which generates the secret)
    $component = Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);

    $component
        ->set('code', '000000')
        ->call('confirmTwoFactor')
        ->assertHasErrors('code');

    $user->refresh();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

// ============================================
// DISABLE 2FA TESTS
// ============================================

test('can disable 2FA', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    // Enable and confirm 2FA first
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
    ])->save();

    Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', true)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull();
});

// ============================================
// RECOVERY CODES TESTS
// ============================================

test('shows recovery codes when 2FA is enabled', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['ABCD-EFGH-IJKL', 'MNOP-QRST-UVWX'])),
    ])->save();

    Livewire::actingAs($user)
        ->test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', true)
        ->assertSee('Recovery Codes');
});

test('can regenerate recovery codes', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    // Enable and confirm 2FA
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['OLD-CODE-1', 'OLD-CODE-2'])),
    ])->save();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Settings\TwoFactor\RecoveryCodes::class)
        ->call('regenerateRecoveryCodes');

    $user->refresh();
    $newCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
    expect($newCodes)->not->toContain('OLD-CODE-1');
});
