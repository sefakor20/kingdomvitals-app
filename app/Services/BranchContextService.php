<?php

namespace App\Services;

use App\Models\Tenant\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class BranchContextService
{
    private const SESSION_KEY = 'current_branch_id';

    public function getCurrentBranchId(): ?string
    {
        return Session::get(self::SESSION_KEY);
    }

    public function getCurrentBranch(): ?Branch
    {
        $branchId = $this->getCurrentBranchId();

        if (! $branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    public function setCurrentBranch(string $branchId): void
    {
        Session::put(self::SESSION_KEY, $branchId);
    }

    public function getDefaultBranchId(?User $user = null): ?string
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return Branch::where('is_main', true)->first()?->id;
        }

        $primaryAccess = $user->branchAccess()
            ->where('is_primary', true)
            ->first();

        if ($primaryAccess) {
            return $primaryAccess->branch_id;
        }

        $firstAccess = $user->branchAccess()->first();

        return $firstAccess?->branch_id
            ?? Branch::where('is_main', true)->first()?->id;
    }

    public function clearContext(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
