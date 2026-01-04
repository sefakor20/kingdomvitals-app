<?php

declare(strict_types=1);

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Include all Fortify authentication routes (login, register, password reset, 2FA, etc.)
    require base_path('vendor/laravel/fortify/routes/routes.php');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    })->name('home');

    // Dashboard
    Route::get('/dashboard', \App\Livewire\Dashboard::class)
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    // Authenticated routes
    Route::middleware(['auth'])->group(function () {
        // Settings
        Route::redirect('settings', 'settings/profile');
        Route::get('settings/profile', Profile::class)->name('profile.edit');
        Route::get('settings/password', Password::class)->name('user-password.edit');
        Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

        // Branch Management
        Route::get('/branches', \App\Livewire\Branches\BranchIndex::class)
            ->name('branches.index');

        // Branch User Management
        Route::get('/branches/{branch}/users', \App\Livewire\Users\BranchUserIndex::class)
            ->name('branches.users.index');

        // Member Management
        Route::get('/branches/{branch}/members', \App\Livewire\Members\MemberIndex::class)
            ->name('members.index');
        Route::get('/branches/{branch}/members/{member}', \App\Livewire\Members\MemberShow::class)
            ->name('members.show');

        // Cluster Management
        Route::get('/branches/{branch}/clusters', \App\Livewire\Clusters\ClusterIndex::class)
            ->name('clusters.index');
        Route::get('/branches/{branch}/clusters/{cluster}', \App\Livewire\Clusters\ClusterShow::class)
            ->name('clusters.show');

        // Service Management
        Route::get('/branches/{branch}/services', \App\Livewire\Services\ServiceIndex::class)
            ->name('services.index');
        Route::get('/branches/{branch}/services/{service}', \App\Livewire\Services\ServiceShow::class)
            ->name('services.show');

        // Attendance Management
        Route::get('/branches/{branch}/attendance', \App\Livewire\Attendance\AttendanceIndex::class)
            ->name('attendance.index');
        Route::get('/branches/{branch}/services/{service}/check-in', \App\Livewire\Attendance\LiveCheckIn::class)
            ->name('attendance.checkin');

        // Visitor Management
        Route::get('/branches/{branch}/visitors', \App\Livewire\Visitors\VisitorIndex::class)
            ->name('visitors.index');
        Route::get('/branches/{branch}/visitors/{visitor}', \App\Livewire\Visitors\VisitorShow::class)
            ->name('visitors.show');

        // Financial Management
        Route::get('/branches/{branch}/finance/dashboard', \App\Livewire\Finance\FinanceDashboard::class)
            ->name('finance.dashboard');
        Route::get('/branches/{branch}/finance/donor-engagement', \App\Livewire\Finance\DonorEngagement::class)
            ->name('finance.donor-engagement');
        Route::get('/branches/{branch}/donations', \App\Livewire\Donations\DonationIndex::class)
            ->name('donations.index');
        Route::get('/branches/{branch}/expenses', \App\Livewire\Expenses\ExpenseIndex::class)
            ->name('expenses.index');
        Route::get('/branches/{branch}/expenses/recurring', \App\Livewire\Expenses\RecurringExpenseIndex::class)
            ->name('expenses.recurring');
        Route::get('/branches/{branch}/pledges', \App\Livewire\Pledges\PledgeIndex::class)
            ->name('pledges.index');
        Route::get('/branches/{branch}/budgets', \App\Livewire\Budgets\BudgetIndex::class)
            ->name('budgets.index');
        Route::get('/branches/{branch}/finance/reports', \App\Livewire\Finance\FinanceReports::class)
            ->name('finance.reports');

        // SMS Management
        Route::get('/branches/{branch}/sms', \App\Livewire\Sms\SmsIndex::class)
            ->name('sms.index');
        Route::get('/branches/{branch}/sms/compose', \App\Livewire\Sms\SmsCompose::class)
            ->name('sms.compose');
        Route::get('/branches/{branch}/sms/templates', \App\Livewire\Sms\SmsTemplateIndex::class)
            ->name('sms.templates');
        Route::get('/branches/{branch}/sms/analytics', \App\Livewire\Sms\SmsAnalytics::class)
            ->name('sms.analytics');

        // Branch Settings
        Route::get('/branches/{branch}/settings', \App\Livewire\Branches\BranchSettings::class)
            ->name('branches.settings');
    });
});
