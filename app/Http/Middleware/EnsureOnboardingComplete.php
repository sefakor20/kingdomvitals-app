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
        // DEBUG: Log auth state on every request (REMOVE AFTER DEBUGGING)
        Log::info('EnsureOnboardingComplete DEBUG', [
            'url' => $request->fullUrl(),
            'is_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'tenant' => tenant()?->id,
        ]);

        // Skip for guests
        if (! auth()->check()) {
            Log::info('EnsureOnboardingComplete: User is guest, passing through');

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

            // Logout user to break potential redirect loop
            // (authenticated user without tenant context causes infinite loop:
            // login -> guest middleware redirects to dashboard -> this middleware redirects to login)
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your session has expired. Please login again.');
        }

        // Check if onboarding is required
        if ($tenant->needsOnboarding()) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
