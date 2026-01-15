<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $centralDomains = config('tenancy.central_domains', []);
            $currentHost = request()->getHost();

            // Super Admin routes (central domain only)
            Route::middleware('web')
                ->domain(config('app.superadmin_domain', 'admin.localhost'))
                ->group(base_path('routes/superadmin.php'));

            // Web routes only for non-central domains
            if (! in_array($currentHost, $centralDomains)) {
                Route::middleware('web')
                    ->group(base_path('routes/web.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('tenant', [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('universal', [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain::class,
        ]);

        // Super admin middleware alias
        $middleware->alias([
            'superadmin' => \App\Http\Middleware\SuperAdminAuthenticate::class,
            'onboarding.complete' => \App\Http\Middleware\EnsureOnboardingComplete::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'quota' => \App\Http\Middleware\EnforceQuota::class,
        ]);

        // Exclude webhook routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
