<?php

declare(strict_types=1);

use App\Http\Controllers\SuperAdmin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SuperAdmin\Auth\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| These routes handle the super admin platform management functionality.
| They are accessed via the admin subdomain (admin.kingdomvitals.com).
|
*/

// Guest routes (not authenticated as super admin)
Route::middleware('guest:superadmin')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('superadmin.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Two-factor authentication challenge
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('superadmin.two-factor.challenge');

    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
});

// Authenticated super admin routes
Route::middleware('superadmin')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('superadmin.logout');

    // Dashboard
    Route::get('/', \App\Livewire\SuperAdmin\Dashboard::class)
        ->name('superadmin.dashboard');

    // Tenant Management
    Route::get('tenants', \App\Livewire\SuperAdmin\Tenants\TenantIndex::class)
        ->name('superadmin.tenants.index');

    Route::get('tenants/{tenant}', \App\Livewire\SuperAdmin\Tenants\TenantShow::class)
        ->name('superadmin.tenants.show')
        ->withTrashed();

    // Subscription Plans
    Route::get('plans', \App\Livewire\SuperAdmin\Plans\PlanIndex::class)
        ->name('superadmin.plans.index');

    // Super Admin Management
    Route::get('admins', \App\Livewire\SuperAdmin\Admins\AdminIndex::class)
        ->name('superadmin.admins.index');

    // Activity Logs
    Route::get('activity-logs', \App\Livewire\SuperAdmin\ActivityLogs::class)
        ->name('superadmin.activity-logs');

    // Revenue Dashboard
    Route::get('revenue', \App\Livewire\SuperAdmin\Revenue\RevenueDashboard::class)
        ->name('superadmin.revenue');

    // Profile/Security
    Route::get('profile/security', \App\Livewire\SuperAdmin\Profile\Security::class)
        ->name('superadmin.profile.security');
});
