<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Expense;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class ExpensePolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any expenses for a branch.
     * All roles can view expenses.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Expenses)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the expense.
     * All roles can view expenses in branches they have access to.
     */
    public function view(User $user, Expense $expense): bool
    {
        if (! $this->moduleEnabled(PlanModule::Expenses)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create expenses.
     * Admin, Manager, and Staff can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Expenses)) {
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
     * Determine whether the user can update the expense.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the expense.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any expense in the branch.
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
     * Determine whether the user can approve expenses.
     * Only Admin and Manager can approve.
     */
    public function approve(User $user, Expense $expense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can reject expenses.
     * Only Admin and Manager can reject.
     */
    public function reject(User $user, Expense $expense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can mark expenses as paid.
     * Only Admin and Manager can mark as paid.
     */
    public function markAsPaid(User $user, Expense $expense): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $expense->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
