<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Pledge;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class PledgePolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any pledges for a branch.
     * All roles can view pledges.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Pledges)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the pledge.
     * All roles can view pledges in branches they have access to.
     */
    public function view(User $user, Pledge $pledge): bool
    {
        if (! $this->moduleEnabled(PlanModule::Pledges)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $pledge->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create pledges.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Pledges)) {
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
     * Determine whether the user can update the pledge.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Pledge $pledge): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pledge->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the pledge.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Pledge $pledge): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pledge->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any pledge in the branch.
     * Only Admin and Manager can delete.
     */
    public function deleteAny(User $user, Branch $branch): bool
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
     * Determine whether the user can record payments for pledges.
     * Admin, Manager, and Staff can record payments.
     */
    public function recordPayment(User $user, Pledge $pledge): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pledge->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
