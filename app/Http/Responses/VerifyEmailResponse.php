<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\Tenant;
use App\Models\Tenant\Member;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        // Check if onboarding needs completion
        $tenant = tenant();
        if ($tenant instanceof Tenant && $tenant->needsOnboarding()) {
            return redirect()->route('onboarding.index');
        }

        // Check if user is a member with portal access
        $user = $request->user();
        if ($user) {
            $member = Member::where('user_id', $user->id)
                ->whereNotNull('portal_activated_at')
                ->first();

            if ($member) {
                return redirect()->route('member.dashboard', ['verified' => 1]);
            }
        }

        return redirect()->intended(config('fortify.home', '/dashboard').'?verified=1');
    }
}
