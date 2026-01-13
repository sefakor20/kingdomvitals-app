<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildMedicalInfo;
use App\Models\User;

/**
 * Policy for Child Medical Info management in the Children module.
 */
class ChildMedicalInfoPolicy
{
    /**
     * Determine whether the user can view any medical info for a branch.
     * All roles can view medical info.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the medical info.
     * All roles can view medical info in branches they have access to.
     */
    public function view(User $user, ChildMedicalInfo $medicalInfo): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $medicalInfo->member->primary_branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create medical info.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
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
     * Determine whether the user can update the medical info.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, ChildMedicalInfo $medicalInfo): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $medicalInfo->member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the medical info.
     * Admin and Manager can delete.
     */
    public function delete(User $user, ChildMedicalInfo $medicalInfo): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $medicalInfo->member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
