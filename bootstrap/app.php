<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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
