<?php

namespace App\Providers;

use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\VisitorFollowUp;
use App\Policies\MemberPolicy;
use App\Policies\UserBranchAccessPolicy;
use App\Policies\VisitorFollowUpPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(UserBranchAccess::class, UserBranchAccessPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(VisitorFollowUp::class, VisitorFollowUpPolicy::class);

        // Load central/landlord migrations
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/landlord'),
        ]);

        // Configure Livewire to use tenant middleware for AJAX updates
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
                ]);
        });
    }
}
