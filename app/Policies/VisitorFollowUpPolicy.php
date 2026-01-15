<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\VisitorFollowUp;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class VisitorFollowUpPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any follow-ups for a branch.
     * All roles can view follow-ups.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the follow-up.
     * All roles can view follow-ups in branches they have access to.
     */
    public function view(User $user, VisitorFollowUp $followUp): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $followUp->visitor->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create follow-ups.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can update the follow-up.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, VisitorFollowUp $followUp): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $followUp->visitor->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the follow-up.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, VisitorFollowUp $followUp): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $followUp->visitor->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
