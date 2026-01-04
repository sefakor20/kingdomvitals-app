<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\RecurringExpense;
use App\Models\User;

class RecurringExpensePolicy
{
    /**
     * Determine whether the user can view any recurring expenses for a branch.
     * All roles can view.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the recurring expense.
     * All roles can view in branches they have access to.
     */
    public function view(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $recurringExpense->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create recurring expenses.
     * Only Admin and Manager can create.
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
     * Determine whether the user can update the recurring expense.
     * Only Admin and Manager can update.
     */
    public function update(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $recurringExpense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the recurring expense.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $recurringExpense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any recurring expense in the branch.
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
     * Determine whether the user can toggle status (pause/resume).
     * Only Admin and Manager can toggle.
     */
    public function toggleStatus(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $recurringExpense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can manually generate an expense.
     * Only Admin and Manager can generate.
     */
    public function generateNow(User $user, RecurringExpense $recurringExpense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $recurringExpense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
