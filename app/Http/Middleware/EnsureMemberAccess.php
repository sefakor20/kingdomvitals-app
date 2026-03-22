<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has an associated member profile
     * with active portal access.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Find member record linked to this user
        $member = Member::where('user_id', $user->id)
            ->whereNotNull('portal_activated_at')
            ->first();

        if (! $member) {
            // Fallback: try to find by email (for backwards compatibility)
            $member = Member::where('email', $user->email)
                ->whereNotNull('portal_activated_at')
                ->first();
        }

        if (! $member) {
            abort(403, __('You do not have member portal access. Please contact your church administrator.'));
        }

        // Share member with all views and bind to container
        view()->share('currentMember', $member);
        app()->instance('currentMember', $member);

        return $next($request);
    }
}
