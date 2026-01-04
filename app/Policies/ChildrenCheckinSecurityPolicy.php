<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\User;

class ChildrenCheckinSecurityPolicy
{
    /**
     * Determine whether the user can view any children check-in security records for a branch.
     * All roles can view.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the children check-in security record.
     * All roles can view records in branches they have access to.
     */
    public function view(User $user, ChildrenCheckinSecurity $security): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $security->attendance->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create children check-in security records.
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
     * Determine whether the user can check out a child.
     * Staff and above can check out.
     */
    public function checkout(User $user, ChildrenCheckinSecurity $security): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $security->attendance->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
