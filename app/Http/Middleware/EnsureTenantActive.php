<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\PlatformInvoice;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if ($request->routeIs(
            'logout',
            'password.*',
            'verification.*',
            'onboarding.*',
            'upgrade.*',
            'plans.*',
            'payment.required',
            'subscription.*',
            'payments.*',
            'invoices.download',
        )) {
            return $next($request);
        }

        if ($request->is('webhooks/*')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return $next($request);
        }

        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        if ($tenant->status === TenantStatus::Inactive || $tenant->status === TenantStatus::Suspended) {
            return redirect()->route('upgrade.required', ['reason' => 'expired']);
        }

        if ($tenant->status === TenantStatus::Trial && (! $tenant->trial_ends_at || $tenant->trial_ends_at->isPast())) {
            return redirect()->route('upgrade.required', ['reason' => 'trial_expired']);
        }

        if (PlatformInvoice::forTenant($tenant->id)->pastDue()->exists()) {
            return redirect()->route('payment.required');
        }

        return $next($request);
    }
}
