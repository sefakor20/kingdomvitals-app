<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureAdminDomain;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SuperAdminRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function (): void {
            if (! file_exists(base_path('routes/superadmin.php'))) {
                return;
            }

            // Register superadmin routes with /admin prefix to avoid collision with tenant Fortify routes
            // The EnsureAdminDomain middleware returns 404 for non-admin domains
            Route::middleware(['web', EnsureAdminDomain::class])
                ->prefix('admin')
                ->group(base_path('routes/superadmin.php'));
        });
    }
}
