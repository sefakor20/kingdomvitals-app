<?php

declare(strict_types=1);

use App\Http\Controllers\SuperAdmin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SuperAdmin\Auth\TwoFactorChallengeController;
use App\Livewire\SuperAdmin\ActivityLogs;
use App\Livewire\SuperAdmin\Admins\AdminIndex;
use App\Livewire\SuperAdmin\Analytics\UsageAnalytics;
use App\Livewire\SuperAdmin\Announcements\AnnouncementIndex;
use App\Livewire\SuperAdmin\Billing\BillingDashboard;
use App\Livewire\SuperAdmin\Billing\InvoiceCreate;
use App\Livewire\SuperAdmin\Billing\InvoiceIndex;
use App\Livewire\SuperAdmin\Billing\InvoiceShow;
use App\Livewire\SuperAdmin\Billing\OverdueInvoices;
use App\Livewire\SuperAdmin\Billing\PaymentIndex;
use App\Livewire\SuperAdmin\Dashboard;
use App\Livewire\SuperAdmin\Plans\PlanIndex;
use App\Livewire\SuperAdmin\Profile\Appearance as ProfileAppearance;
use App\Livewire\SuperAdmin\Profile\Password as ProfilePassword;
use App\Livewire\SuperAdmin\Profile\Profile as ProfileEdit;
use App\Livewire\SuperAdmin\Profile\Security;
use App\Livewire\SuperAdmin\Revenue\RevenueDashboard;
use App\Livewire\SuperAdmin\Settings\SystemSettings;
use App\Livewire\SuperAdmin\SystemLogs;
use App\Livewire\SuperAdmin\Tenants\TenantIndex;
use App\Livewire\SuperAdmin\Tenants\TenantShow;
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
Route::middleware('guest:superadmin')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('superadmin.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Two-factor authentication challenge
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('superadmin.two-factor.challenge');

    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
});

// Authenticated super admin routes
Route::middleware('superadmin')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('superadmin.logout');

    // Dashboard
    Route::get('/', Dashboard::class)
        ->name('superadmin.dashboard');

    // Tenant Management
    Route::get('tenants', TenantIndex::class)
        ->name('superadmin.tenants.index');

    Route::get('tenants/{tenant}', TenantShow::class)
        ->name('superadmin.tenants.show')
        ->withTrashed();

    // Subscription Plans
    Route::get('plans', PlanIndex::class)
        ->name('superadmin.plans.index');

    // Announcements
    Route::get('announcements', AnnouncementIndex::class)
        ->name('superadmin.announcements.index');

    // Super Admin Management
    Route::get('admins', AdminIndex::class)
        ->name('superadmin.admins.index');

    // Activity Logs
    Route::get('activity-logs', ActivityLogs::class)
        ->name('superadmin.activity-logs');

    // System Logs (Laravel application logs)
    Route::get('system-logs', SystemLogs::class)
        ->name('superadmin.system-logs');

    // Revenue Dashboard
    Route::get('revenue', RevenueDashboard::class)
        ->name('superadmin.revenue');

    // Usage Analytics Dashboard
    Route::get('analytics/usage', UsageAnalytics::class)
        ->name('superadmin.analytics.usage');

    // System Settings
    Route::get('settings', SystemSettings::class)
        ->name('superadmin.settings');

    // Billing Management
    Route::get('billing', BillingDashboard::class)
        ->name('superadmin.billing.dashboard');

    Route::get('billing/invoices', InvoiceIndex::class)
        ->name('superadmin.billing.invoices');

    Route::get('billing/invoices/create', InvoiceCreate::class)
        ->name('superadmin.billing.invoices.create');

    Route::get('billing/invoices/{invoice}', InvoiceShow::class)
        ->name('superadmin.billing.invoices.show');

    Route::get('billing/payments', PaymentIndex::class)
        ->name('superadmin.billing.payments');

    Route::get('billing/overdue', OverdueInvoices::class)
        ->name('superadmin.billing.overdue');

    // Profile / Account Settings
    Route::get('profile/edit', ProfileEdit::class)
        ->name('superadmin.profile.edit');

    Route::get('profile/password', ProfilePassword::class)
        ->name('superadmin.profile.password');

    Route::get('profile/security', Security::class)
        ->name('superadmin.profile.security');

    Route::get('profile/appearance', ProfileAppearance::class)
        ->name('superadmin.profile.appearance');
});
