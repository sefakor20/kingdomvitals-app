<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\FollowUpTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class FollowUpTemplatePolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any templates for a branch.
     * All roles can view templates.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the template.
     * All roles can view templates in branches they have access to.
     */
    public function view(User $user, FollowUpTemplate $template): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $template->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create templates.
     * Only Admin and Manager can create.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
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
     * Determine whether the user can update the template.
     * Only Admin and Manager can update.
     */
    public function update(User $user, FollowUpTemplate $template): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $template->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the template.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, FollowUpTemplate $template): bool
    {
        if (! $this->moduleEnabled(PlanModule::Visitors)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $template->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
