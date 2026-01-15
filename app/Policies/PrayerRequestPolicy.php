<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Enums\PrayerRequestPrivacy;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PrayerRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class PrayerRequestPolicy
{
    use ChecksPlanAccess;

    /**
     * All roles with branch access can view prayer requests.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::PrayerRequests)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * View a prayer request based on privacy level.
     * - Public: all branch members
     * - Private: only Admin and Manager
     * - LeadersOnly: Admin, Manager, and Staff
     */
    public function view(User $user, PrayerRequest $prayerRequest): bool
    {
        if (! $this->moduleEnabled(PlanModule::PrayerRequests)) {
            return false;
        }

        $branchAccess = $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->first();

        if (! $branchAccess) {
            return false;
        }

        return match ($prayerRequest->privacy) {
            PrayerRequestPrivacy::Public => true,
            PrayerRequestPrivacy::LeadersOnly => in_array($branchAccess->role, [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ]),
            PrayerRequestPrivacy::Private => in_array($branchAccess->role, [
                BranchRole::Admin,
                BranchRole::Manager,
            ]),
        };
    }

    /**
     * Admin, Manager, and Staff can create prayer requests.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::PrayerRequests)) {
            return false;
        }

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
     * Admin, Manager, and Staff can update prayer requests.
     */
    public function update(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Only Admin and Manager can delete prayer requests.
     */
    public function delete(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
            ])
            ->exists();
    }

    /**
     * Admin, Manager, and Staff can mark prayer requests as answered.
     */
    public function markAnswered(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }

    /**
     * Add an update to a prayer request based on privacy level.
     * - Public: all branch members
     * - LeadersOnly: Admin, Manager, and Staff
     * - Private: only Admin and Manager
     */
    public function addUpdate(User $user, PrayerRequest $prayerRequest): bool
    {
        $branchAccess = $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->first();

        if (! $branchAccess) {
            return false;
        }

        return match ($prayerRequest->privacy) {
            PrayerRequestPrivacy::Public => true,
            PrayerRequestPrivacy::LeadersOnly => in_array($branchAccess->role, [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ]),
            PrayerRequestPrivacy::Private => in_array($branchAccess->role, [
                BranchRole::Admin,
                BranchRole::Manager,
            ]),
        };
    }

    /**
     * Admin, Manager, and Staff can send prayer chain notifications.
     */
    public function sendPrayerChain(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $prayerRequest->branch_id)
            ->whereIn('role', [
                BranchRole::Admin,
                BranchRole::Manager,
                BranchRole::Staff,
            ])
            ->exists();
    }
}
