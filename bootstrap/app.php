<?php

use App\Http\Middleware\EnforceQuota;
use App\Http\Middleware\EnsureMemberAccess;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\SuperAdminAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\OgImage\Http\Middleware\RenderOgImageMiddleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (for Laravel Forge / nginx reverse proxy)
        // This ensures Laravel correctly detects HTTPS requests behind nginx
        $middleware->trustProxies(at: '*');

        // Configure where authenticated users are redirected when accessing guest routes
        $middleware->redirectUsersTo(function (Request $request) {
            // For superadmin guard, redirect to superadmin dashboard
            if ($request->routeIs('superadmin.*') || str_starts_with($request->getHost(), 'admin.')) {
                return route('superadmin.dashboard');
            }

            return '/dashboard';
        });

        $middleware->group('tenant', [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('universal', [
            InitializeTenancyByDomainOrSubdomain::class,
        ]);

        // Super admin middleware alias
        $middleware->alias([
            'superadmin' => SuperAdminAuthenticate::class,
            'onboarding.complete' => EnsureOnboardingComplete::class,
            'module' => EnsureModuleEnabled::class,
            'quota' => EnforceQuota::class,
            'member' => EnsureMemberAccess::class,
        ]);

        // Exclude webhook routes from request forgery protection
        $middleware->preventRequestForgery(except: [
            'webhooks/*',
        ]);

        // Append OG Image middleware to the web group
        $middleware->appendToGroup('web', RenderOgImageMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
