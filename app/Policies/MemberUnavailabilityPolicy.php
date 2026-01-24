<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\MemberUnavailability;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class MemberUnavailabilityPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any unavailabilities for a branch.
     * All roles can view unavailabilities.
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
     * Determine whether the user can view the unavailability.
     * All roles can view unavailabilities in branches they have access to.
     */
    public function view(User $user, MemberUnavailability $unavailability): bool
    {
        if (! $this->moduleEnabled(PlanModule::DutyRoster)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $unavailability->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create unavailabilities.
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
     * Determine whether the user can update the unavailability.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, MemberUnavailability $unavailability): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $unavailability->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the unavailability.
     * Admin, Manager, and Staff can delete.
     */
    public function delete(User $user, MemberUnavailability $unavailability): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $unavailability->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
