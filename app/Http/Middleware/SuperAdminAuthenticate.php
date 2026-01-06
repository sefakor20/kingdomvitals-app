<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('superadmin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('superadmin.login');
        }

        $superAdmin = Auth::guard('superadmin')->user();

        // Check if account is active
        if (! $superAdmin->is_active) {
            Auth::guard('superadmin')->logout();

            return redirect()->route('superadmin.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Check if account is locked
        if ($superAdmin->isLocked()) {
            Auth::guard('superadmin')->logout();

            return redirect()->route('superadmin.login')
                ->withErrors(['email' => 'Your account is temporarily locked. Please try again later.']);
        }

        return $next($request);
    }
}
