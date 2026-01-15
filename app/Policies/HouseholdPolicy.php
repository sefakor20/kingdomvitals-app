<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class HouseholdPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any households for a branch.
     * All roles can view households.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Households)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the household.
     * All roles can view households in branches they have access to.
     */
    public function view(User $user, Household $household): bool
    {
        if (! $this->moduleEnabled(PlanModule::Households)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $household->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create households.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Households)) {
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
     * Determine whether the user can update the household.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Household $household): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $household->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the household.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Household $household): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $household->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
