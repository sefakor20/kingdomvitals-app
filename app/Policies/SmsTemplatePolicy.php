<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class SmsTemplatePolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any SMS templates for a branch.
     * All roles can view SMS templates.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Sms)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the SMS template.
     * All roles can view SMS templates in branches they have access to.
     */
    public function view(User $user, SmsTemplate $smsTemplate): bool
    {
        if (! $this->moduleEnabled(PlanModule::Sms)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $smsTemplate->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create SMS templates.
     * Admin and Manager can create templates.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Sms)) {
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
     * Determine whether the user can update the SMS template.
     * Admin and Manager can update templates.
     */
    public function update(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $smsTemplate->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the SMS template.
     * Only Admin can delete templates.
     */
    public function delete(User $user, SmsTemplate $smsTemplate): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $smsTemplate->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }
}
