<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;

class UserBranchAccessPolicy
{
    /**
     * Determine whether the user can view user access list for a branch.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }

    /**
     * Determine whether the user can add users to a branch.
     */
    public function create(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }

    /**
     * Determine whether the user can update a user's branch access.
     */
    public function update(User $user, UserBranchAccess $access): bool
    {
        // Cannot edit own access
        if ($access->user_id === $user->id) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $access->branch_id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }

    /**
     * Determine whether the user can revoke a user's branch access.
     */
    public function delete(User $user, UserBranchAccess $access): bool
    {
        // Cannot revoke own access
        if ($access->user_id === $user->id) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $access->branch_id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }
}
