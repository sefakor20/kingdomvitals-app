<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Models\SuperAdminActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('superadmin.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $superAdmin = SuperAdmin::where('email', $request->email)->first();

        if (! $superAdmin) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Check if account is locked
        if ($superAdmin->isLocked()) {
            throw ValidationException::withMessages([
                'email' => __('Your account is temporarily locked. Please try again later.'),
            ]);
        }

        // Check if account is active
        if (! $superAdmin->is_active) {
            throw ValidationException::withMessages([
                'email' => __('Your account has been deactivated.'),
            ]);
        }

        // Verify password
        if (! Hash::check($request->password, $superAdmin->password)) {
            $superAdmin->recordFailedLogin();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Check if 2FA is enabled - redirect to challenge instead of logging in
        if ($superAdmin->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'superadmin.login.id' => $superAdmin->id,
                'superadmin.login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('superadmin.two-factor.challenge');
        }

        // Log in the super admin (no 2FA)
        Auth::guard('superadmin')->login($superAdmin, $request->boolean('remember'));

        $request->session()->regenerate();

        // Record successful login
        $superAdmin->recordLogin($request->ip());

        // Log the activity
        SuperAdminActivityLog::log(
            superAdmin: $superAdmin,
            action: 'login',
            description: 'Super admin logged in',
        );

        return redirect()->route('superadmin.dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $superAdmin = Auth::guard('superadmin')->user();

        if ($superAdmin) {
            SuperAdminActivityLog::log(
                superAdmin: $superAdmin,
                action: 'logout',
                description: 'Super admin logged out',
            );
        }

        Auth::guard('superadmin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }
}
