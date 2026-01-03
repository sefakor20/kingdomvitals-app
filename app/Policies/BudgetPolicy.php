<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view any budgets for a branch.
     * All roles can view budgets.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the budget.
     * All roles can view budgets in branches they have access to.
     */
    public function view(User $user, Budget $budget): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $budget->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create budgets.
     * Only Admin and Manager can create budgets.
     */
    public function create(User $user, Branch $branch): bool
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
     * Determine whether the user can update the budget.
     * Only Admin and Manager can update.
     */
    public function update(User $user, Budget $budget): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $budget->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the budget.
     * Only Admin can delete budgets.
     */
    public function delete(User $user, Budget $budget): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $budget->branch_id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }

    /**
     * Determine whether the user can delete any budget in the branch.
     * Only Admin can delete budgets.
     */
    public function deleteAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->where('role', BranchRole::Admin->value)
            ->exists();
    }
}
