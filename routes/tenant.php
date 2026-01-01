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
    Route::view('/dashboard', 'dashboard')
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

        // Visitor Management
        Route::get('/branches/{branch}/visitors', \App\Livewire\Visitors\VisitorIndex::class)
            ->name('visitors.index');
        Route::get('/branches/{branch}/visitors/{visitor}', \App\Livewire\Visitors\VisitorShow::class)
            ->name('visitors.show');
    });
});
