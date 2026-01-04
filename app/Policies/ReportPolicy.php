<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view reports for a branch.
     * Admin and Manager can view reports.
     */
    public function viewReports(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can export reports.
     * Admin and Manager can export.
     */
    public function exportReports(User $user, Branch $branch): bool
    {
        return $this->viewReports($user, $branch);
    }
}
