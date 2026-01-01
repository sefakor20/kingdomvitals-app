<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Determine whether the user can view any services for a branch.
     * All roles can view services.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the service.
     * All roles can view services in branches they have access to.
     */
    public function view(User $user, Service $service): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $service->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create services.
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
     * Determine whether the user can update the service.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Service $service): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $service->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the service.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Service $service): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $service->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any service in the branch.
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
