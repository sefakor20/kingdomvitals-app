<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\User;

/**
 * Policy for Age Group management in the Children module.
 */
class AgeGroupPolicy
{
    /**
     * Determine whether the user can view any age groups for a branch.
     * All roles can view age groups.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the age group.
     * All roles can view age groups in branches they have access to.
     */
    public function view(User $user, AgeGroup $ageGroup): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $ageGroup->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create age groups.
     * Admin and Manager can create.
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
     * Determine whether the user can update the age group.
     * Admin and Manager can update.
     */
    public function update(User $user, AgeGroup $ageGroup): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $ageGroup->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the age group.
     * Only Admin can delete.
     */
    public function delete(User $user, AgeGroup $ageGroup): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $ageGroup->branch_id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }
}
