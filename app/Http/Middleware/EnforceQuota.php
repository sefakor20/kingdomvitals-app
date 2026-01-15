<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PlanAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceQuota
{
    public function __construct(
        private PlanAccessService $planAccess
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $quotaType  'members', 'branches', or 'sms'
     */
    public function handle(Request $request, Closure $next, string $quotaType): Response
    {
        // Skip for guests
        if (! auth()->check()) {
            return $next($request);
        }

        $exceeded = match ($quotaType) {
            'members' => ! $this->planAccess->canCreateMember(),
            'branches' => ! $this->planAccess->canCreateBranch(),
            'sms' => ! $this->planAccess->canSendSms(),
            default => false,
        };

        if ($exceeded) {
            $quota = match ($quotaType) {
                'members' => $this->planAccess->getMemberQuota(),
                'branches' => $this->planAccess->getBranchQuota(),
                'sms' => $this->planAccess->getSmsQuota(),
                default => [],
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __("You have reached your {$quotaType} limit for your current plan."),
                    'quota_type' => $quotaType,
                    'quota' => $quota,
                    'upgrade_required' => true,
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->back()->with('error',
                __("You have reached your {$quotaType} limit. Please upgrade your plan to continue.")
            );
        }

        return $next($request);
    }
}
