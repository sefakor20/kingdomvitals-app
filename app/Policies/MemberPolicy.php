<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can view any members for a branch.
     * All roles can view members.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the member.
     * All roles can view members in branches they have access to.
     */
    public function view(User $user, Member $member): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $member->primary_branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create members.
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
     * Determine whether the user can update the member.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Member $member): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the member.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
