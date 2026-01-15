<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class ClusterPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any clusters for a branch.
     * All roles can view clusters.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Clusters)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the cluster.
     * All roles can view clusters in branches they have access to.
     */
    public function view(User $user, Cluster $cluster): bool
    {
        if (! $this->moduleEnabled(PlanModule::Clusters)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $cluster->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create clusters.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Clusters)) {
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
     * Determine whether the user can update the cluster.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Cluster $cluster): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $cluster->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the cluster.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Cluster $cluster): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $cluster->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any cluster in the branch.
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
