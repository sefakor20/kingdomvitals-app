<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class EmailTemplatePolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any email templates for a branch.
     * All roles can view email templates.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the email template.
     * All roles can view email templates in branches they have access to.
     */
    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $emailTemplate->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create email templates.
     * Admin and Manager can create templates.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Email)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can update the email template.
     * Admin and Manager can update templates.
     */
    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $emailTemplate->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the email template.
     * Only Admin can delete templates.
     */
    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $emailTemplate->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }
}
