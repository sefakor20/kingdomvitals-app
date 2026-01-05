<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * All roles with branch access can view equipment.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can create equipment.
     */
    public function create(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can update equipment.
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Only Admin and Manager can delete equipment.
     */
    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
            ])
            ->exists();
    }

    /**
     * Only Admin and Manager can delete any equipment.
     */
    public function deleteAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
            ])
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can process checkouts.
     */
    public function checkout(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Only Admin and Manager can approve checkout requests.
     */
    public function approveCheckout(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
            ])
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can process returns.
     */
    public function processReturn(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can manage maintenance.
     */
    public function manageMaintenance(User $user, Equipment $equipment): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $equipment->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Only Admin and Manager can view analytics.
     */
    public function viewAnalytics(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
            ])
            ->exists();
    }
}
