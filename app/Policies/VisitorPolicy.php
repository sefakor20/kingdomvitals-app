<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use App\Models\User;

class VisitorPolicy
{
    /**
     * Determine whether the user can view any visitors for a branch.
     * All roles can view visitors.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the visitor.
     * All roles can view visitors in branches they have access to.
     */
    public function view(User $user, Visitor $visitor): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $visitor->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create visitors.
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
     * Determine whether the user can update the visitor.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Visitor $visitor): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $visitor->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the visitor.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Visitor $visitor): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $visitor->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any visitor in the branch.
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
}
