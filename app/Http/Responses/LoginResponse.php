<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\Tenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        // Check if email needs verification
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Check if onboarding needs completion
        $tenant = tenant();
        if ($tenant instanceof Tenant && $tenant->needsOnboarding()) {
            return redirect()->route('onboarding.index');
        }

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }
}
