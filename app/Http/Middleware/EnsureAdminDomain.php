<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureAdminDomain
{
    /**
     * Handle an incoming request.
     *
     * Ensures the request is coming from an admin domain.
     * This middleware protects superadmin routes from being accessed on tenant domains.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentHost = $request->getHost();
        $superadminDomain = config('app.superadmin_domain', 'admin.localhost');

        // Allow access if:
        // 1. Current host matches the configured superadmin domain exactly
        // 2. Current host starts with 'admin.' (admin subdomain pattern)
        if ($currentHost === $superadminDomain || str_starts_with($currentHost, 'admin.')) {
            return $next($request);
        }

        // Not an admin domain - return 404 to avoid leaking route information
        throw new NotFoundHttpException;
    }
}
