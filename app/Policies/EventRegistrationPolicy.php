<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EventRegistration;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class EventRegistrationPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any registrations for a branch.
     * All roles can view registrations.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Events)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the registration.
     * Users can view their own registration or staff can view any.
     */
    public function view(User $user, EventRegistration $registration): bool
    {
        if (! $this->moduleEnabled(PlanModule::Events)) {
            return false;
        }

        // Check if user is viewing their own registration
        $userMemberId = $user->member?->id;
        if ($userMemberId && $registration->member_id === $userMemberId) {
            return true;
        }

        return $user->branchAccess()
            ->where('branch_id', $registration->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create registrations.
     * Admin, Manager, and Staff can create registrations on behalf of others.
     */
    public function create(User $user, Branch $branch): bool
    {
        if (! $this->moduleEnabled(PlanModule::Events)) {
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
     * Determine whether the user can update the registration.
     * Users can update their own registration or staff can update any.
     */
    public function update(User $user, EventRegistration $registration): bool
    {
        // Check if user is updating their own registration
        $userMemberId = $user->member?->id;
        if ($userMemberId && $registration->member_id === $userMemberId) {
            return true;
        }

        return $user->branchAccess()
            ->where('branch_id', $registration->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can cancel the registration.
     * Users can cancel their own registration or staff can cancel any.
     */
    public function cancel(User $user, EventRegistration $registration): bool
    {
        return $this->update($user, $registration);
    }

    /**
     * Determine whether the user can delete the registration.
     * Only Admin and Manager can delete.
     */
    public function delete(User $user, EventRegistration $registration): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $registration->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can check in for this registration.
     * Admin, Manager, and Staff can check in attendees.
     */
    public function checkIn(User $user, EventRegistration $registration): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $registration->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
