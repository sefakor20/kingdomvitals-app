<?php

use App\Http\Controllers\Webhooks\TextTangoWebhookController;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function (): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse {
    // Redirect admin domain to admin login
    $currentHost = request()->getHost();
    $superadminDomain = config('app.superadmin_domain', 'admin.localhost');

    if ($currentHost === $superadminDomain || str_starts_with($currentHost, 'admin.')) {
        return redirect()->route('superadmin.login');
    }

    return view('landing.index');
})->name('home');

// Onboarding routes (auth but no onboarding.complete middleware)
Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
    Route::get('/', OnboardingWizard::class)->name('index');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'onboarding.complete'])
    ->name('dashboard');

Route::middleware(['auth', 'onboarding.complete'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Webhook routes (no auth, no tenant middleware)
Route::post('/webhooks/texttango/delivery', [TextTangoWebhookController::class, 'handleDelivery'])
    ->name('webhooks.texttango.delivery');
