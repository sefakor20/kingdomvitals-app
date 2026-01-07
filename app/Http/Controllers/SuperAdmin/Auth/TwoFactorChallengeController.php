<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the two-factor challenge view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('superadmin.login.id')) {
            return redirect()->route('superadmin.login');
        }

        return view('superadmin.auth.two-factor-challenge');
    }

    /**
     * Handle the two-factor authentication challenge.
     */
    public function store(Request $request): RedirectResponse
    {
        $superAdminId = $request->session()->get('superadmin.login.id');
        $remember = $request->session()->get('superadmin.login.remember', false);

        if (! $superAdminId) {
            return redirect()->route('superadmin.login');
        }

        $superAdmin = SuperAdmin::find($superAdminId);

        if (! $superAdmin) {
            $request->session()->forget(['superadmin.login.id', 'superadmin.login.remember']);

            return redirect()->route('superadmin.login');
        }

        // Validate either code or recovery_code
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        // Try OTP code first
        if ($code) {
            if (! $this->verifyTwoFactorCode($superAdmin, $code)) {
                throw ValidationException::withMessages([
                    'code' => __('The provided two-factor authentication code was invalid.'),
                ]);
            }
        }
        // Try recovery code
        elseif ($recoveryCode) {
            if (! $this->verifyRecoveryCode($superAdmin, $recoveryCode)) {
                throw ValidationException::withMessages([
                    'recovery_code' => __('The provided two-factor recovery code was invalid.'),
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'code' => __('Please provide a two-factor authentication code or recovery code.'),
            ]);
        }

        // Clear the session login data
        $request->session()->forget(['superadmin.login.id', 'superadmin.login.remember']);

        // Log in the super admin
        Auth::guard('superadmin')->login($superAdmin, $remember);

        $request->session()->regenerate();

        // Record successful login
        $superAdmin->recordLogin($request->ip());

        // Log the activity
        SuperAdminActivityLog::log(
            superAdmin: $superAdmin,
            action: 'login',
            description: 'Super admin logged in with 2FA',
        );

        return redirect()->route('superadmin.dashboard');
    }

    /**
     * Verify the two-factor authentication code.
     */
    private function verifyTwoFactorCode(SuperAdmin $superAdmin, string $code): bool
    {
        $codeVerified = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)
            ->verify(decrypt($superAdmin->two_factor_secret), $code);

        return $codeVerified;
    }

    /**
     * Verify the recovery code.
     */
    private function verifyRecoveryCode(SuperAdmin $superAdmin, string $code): bool
    {
        $recoveryCodes = json_decode(decrypt($superAdmin->two_factor_recovery_codes), true);

        if (! is_array($recoveryCodes)) {
            return false;
        }

        $code = str_replace('-', '', $code);

        foreach ($recoveryCodes as $index => $recoveryCode) {
            if (hash_equals($code, str_replace('-', '', $recoveryCode))) {
                // Remove the used recovery code
                unset($recoveryCodes[$index]);
                $superAdmin->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
                ])->save();

                return true;
            }
        }

        return false;
    }
}
