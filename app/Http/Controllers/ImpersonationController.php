<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BranchRole;
use App\Models\User;
use App\Services\TenantImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function __construct(
        private TenantImpersonationService $impersonationService
    ) {}

    public function enter(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (! $token) {
            abort(403, 'Invalid impersonation request');
        }

        $log = $this->impersonationService->getActiveSession($token);

        if (!$log instanceof \App\Models\TenantImpersonationLog) {
            abort(403, 'Impersonation session expired or invalid');
        }

        // Get a user with admin branch access, or fall back to any user
        $user = User::whereHas('branchAccess', function ($query): void {
            $query->where('role', BranchRole::Admin->value);
        })->first() ?? User::first();

        if (! $user) {
            abort(404, 'No users found in tenant');
        }

        // Store impersonation data in session
        Session::put(TenantImpersonationService::SESSION_KEY, [
            'token' => $token,
            'super_admin_id' => $log->super_admin_id,
            'super_admin_name' => $log->superAdmin->name,
            'started_at' => $log->started_at->toIso8601String(),
        ]);

        // Log in as the tenant user
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function exit(Request $request): RedirectResponse
    {
        $sessionData = Session::get(TenantImpersonationService::SESSION_KEY);

        if ($sessionData && isset($sessionData['token'])) {
            $this->impersonationService->endImpersonation($sessionData['token']);
        }

        // Clear the impersonation session
        Session::forget(TenantImpersonationService::SESSION_KEY);

        // Logout from tenant
        Auth::logout();

        // Redirect back to super admin panel
        $adminDomain = config('app.superadmin_domain', 'admin.localhost');
        $scheme = app()->isProduction() ? 'https' : 'http';

        return redirect()->away("{$scheme}://{$adminDomain}");
    }
}
