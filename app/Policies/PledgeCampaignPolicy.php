<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PledgeCampaign;
use App\Models\User;

class PledgeCampaignPolicy
{
    /**
     * Determine whether the user can view any campaigns for a branch.
     * All roles can view campaigns.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the campaign.
     * All roles can view campaigns in branches they have access to.
     */
    public function view(User $user, PledgeCampaign $campaign): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $campaign->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create campaigns.
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
     * Determine whether the user can update the campaign.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, PledgeCampaign $campaign): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $campaign->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the campaign.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, PledgeCampaign $campaign): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $campaign->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any campaign in the branch.
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
