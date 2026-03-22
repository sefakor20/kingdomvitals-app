<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class EmailLogPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any email logs for a branch.
     * All roles can view email logs.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the email log.
     * All roles can view email logs in branches they have access to.
     */
    public function view(User $user, EmailLog $emailLog): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $emailLog->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create email logs (send emails).
     * Admin and Manager can send emails.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the email log.
     * Only Admin can delete email logs.
     */
    public function delete(User $user, EmailLog $emailLog): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $emailLog->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }
}
