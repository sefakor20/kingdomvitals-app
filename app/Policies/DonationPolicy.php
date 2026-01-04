<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\User;

class DonationPolicy
{
    /**
     * Determine whether the user can view any donations for a branch.
     * All roles can view donations.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the donation.
     * All roles can view donations in branches they have access to.
     */
    public function view(User $user, Donation $donation): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $donation->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create donations.
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
     * Determine whether the user can update the donation.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Donation $donation): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $donation->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the donation.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Donation $donation): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $donation->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any donation in the branch.
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
     * Determine whether the user can view financial reports for the branch.
     * Only Admin and Manager can view reports.
     */
    public function viewReports(User $user, Branch $branch): bool
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
     * Determine whether the user can generate receipts for donations.
     * All roles with branch access can generate/download receipts.
     */
    public function generateReceipt(User $user, Donation $donation): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $donation->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can send receipt emails.
     * Only Admin, Manager, and Staff can send emails.
     */
    public function sendReceipt(User $user, Donation $donation): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $donation->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
