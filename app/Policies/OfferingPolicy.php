<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

/**
 * Policy for Regular Offerings module.
 * Offerings are stored as Donations with donation_type = 'offering',
 * so this policy mirrors the DonationPolicy logic.
 */
class OfferingPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any offerings for a branch.
     * All roles can view offerings.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Donations)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the offering.
     * All roles can view offerings in branches they have access to.
     */
    public function view(User $user, Donation $offering): bool
    {
        if (! $this->moduleEnabled(PlanModule::Donations)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $offering->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create offerings.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Donations)) {
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
     * Determine whether the user can update the offering.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Donation $offering): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $offering->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the offering.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Donation $offering): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $offering->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any offering in the branch.
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
