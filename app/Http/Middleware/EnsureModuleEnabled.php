<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PlanModule;
use App\Services\PlanAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(
        private PlanAccessService $planAccess
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $module  Optional specific module to check
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        // Skip for guests
        if (! auth()->check()) {
            return $next($request);
        }

        // Determine which module to check
        $moduleToCheck = $module
            ? PlanModule::tryFrom($module)
            : PlanModule::fromRouteName($request->route()?->getName() ?? '');

        // If no module mapping found, allow access
        if (! $moduleToCheck) {
            return $next($request);
        }

        // Check if module is enabled
        if (! $this->planAccess->hasModule($moduleToCheck)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('This feature is not available on your current plan.'),
                    'module' => $moduleToCheck->value,
                    'module_label' => $moduleToCheck->label(),
                    'upgrade_required' => true,
                ], Response::HTTP_FORBIDDEN);
            }

            // Redirect to upgrade page with module info
            return redirect()->route('upgrade.required', [
                'module' => $moduleToCheck->value,
            ]);
        }

        return $next($request);
    }
}
