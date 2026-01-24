<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRosterPool;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class DutyRosterPoolPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any duty roster pools for a branch.
     * All roles can view pools.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::DutyRoster)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the duty roster pool.
     * All roles can view pools in branches they have access to.
     */
    public function view(User $user, DutyRosterPool $pool): bool
    {
        if (! $this->moduleEnabled(PlanModule::DutyRoster)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $pool->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create duty roster pools.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::DutyRoster)) {
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
     * Determine whether the user can update the duty roster pool.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, DutyRosterPool $pool): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pool->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the duty roster pool.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, DutyRosterPool $pool): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pool->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can manage members in the pool.
     * Admin, Manager, and Staff can manage pool members.
     */
    public function manageMembers(User $user, DutyRosterPool $pool): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $pool->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
