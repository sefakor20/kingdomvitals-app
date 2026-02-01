<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\OnboardingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function __construct(
        protected OnboardingService $onboardingService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for guests
        if (! auth()->check()) {
            return $next($request);
        }

        // Skip for onboarding routes themselves
        if ($request->routeIs('onboarding.*')) {
            return $next($request);
        }

        // Skip for logout and other essential routes
        if ($request->routeIs('logout', 'password.*', 'verification.*')) {
            return $next($request);
        }

        // Skip for webhook routes
        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        // Skip for API requests (they handle their own logic)
        if ($request->expectsJson()) {
            return $next($request);
        }

        // Ensure tenant context exists for protected routes
        $tenant = tenant();
        if (! $tenant instanceof Tenant) {
            Log::error('Tenant context missing in onboarding middleware', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            // Redirect to login to re-establish tenant context
            return redirect()->route('login');
        }

        // Check if onboarding is required
        if ($tenant->needsOnboarding()) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
