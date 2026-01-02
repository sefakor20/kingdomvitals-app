<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Models\User;

class SmsLogPolicy
{
    /**
     * Determine whether the user can view any SMS logs for a branch.
     * All roles can view SMS logs.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the SMS log.
     * All roles can view SMS logs in branches they have access to.
     */
    public function view(User $user, SmsLog $smsLog): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $smsLog->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create SMS logs (send SMS).
     * Admin and Manager can send SMS.
     */
    public function create(User $user, Branch $branch): bool
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
     * Determine whether the user can delete the SMS log.
     * Only Admin can delete SMS logs.
     */
    public function delete(User $user, SmsLog $smsLog): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $smsLog->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }
}
