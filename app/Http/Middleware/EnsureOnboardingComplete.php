<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OnboardingService;
use Closure;
use Illuminate\Http\Request;
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

        // Check if onboarding is required
        if ($this->onboardingService->isOnboardingRequired()) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
