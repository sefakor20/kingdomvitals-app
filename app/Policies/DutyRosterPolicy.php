<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class DutyRosterPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any duty rosters for a branch.
     * All roles can view duty rosters.
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
     * Determine whether the user can view the duty roster.
     * All roles can view duty rosters in branches they have access to.
     */
    public function view(User $user, DutyRoster $dutyRoster): bool
    {
        if (! $this->moduleEnabled(PlanModule::DutyRoster)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $dutyRoster->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create duty rosters.
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
     * Determine whether the user can update the duty roster.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, DutyRoster $dutyRoster): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $dutyRoster->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the duty roster.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, DutyRoster $dutyRoster): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $dutyRoster->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any duty roster in the branch.
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

    /**
     * Determine whether the user can publish/unpublish duty rosters.
     * Only Admin and Manager can publish.
     */
    public function publish(User $user, DutyRoster $dutyRoster): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $dutyRoster->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can generate duty rosters.
     * Admin, Manager, and Staff can generate.
     */
    public function generate(User $user, Branch $branch): bool
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
}
