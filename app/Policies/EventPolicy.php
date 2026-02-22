<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BranchRole;
use App\Enums\PlanModule;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksPlanAccess;

class EventPolicy
{
    use ChecksPlanAccess;

    /**
     * Determine whether the user can view any events for a branch.
     * All roles can view events.
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
     * Determine whether the user can view the event.
     * All roles can view events in branches they have access to.
     */
    public function view(User $user, Event $event): bool
    {
        if (! $this->moduleEnabled(PlanModule::Events)) {
            return false;
        }

        return $user->branchAccess()
            ->where('branch_id', $event->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create events.
     * Admin, Manager, and Staff can create.
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
     * Determine whether the user can update the event.
     * Admin, Manager, Staff, or the event organizer can update.
     */
    public function update(User $user, Event $event): bool
    {
        // Check if user is the organizer (via member relationship)
        $userMemberId = $user->member?->id;
        if ($userMemberId && $event->organizer_id === $userMemberId) {
            return true;
        }

        return $user->branchAccess()
            ->where('branch_id', $event->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the event.
     * Only Admin can delete, or the event organizer.
     */
    public function delete(User $user, Event $event): bool
    {
        // Check if user is the organizer
        $userMemberId = $user->member?->id;
        if ($userMemberId && $event->organizer_id === $userMemberId) {
            return true;
        }

        return $user->branchAccess()
            ->where('branch_id', $event->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete any event in the branch.
     * Only Admin can delete.
     */
    public function deleteAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->whereIn('role', [
                BranchRole::Admin->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can publish the event.
     * Admin, Manager, and Staff can publish.
     */
    public function publish(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    /**
     * Determine whether the user can manage registrations.
     * Admin, Manager, and Staff can manage registrations.
     */
    public function manageRegistrations(User $user, Event $event): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $event->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can check in attendees.
     * Admin, Manager, and Staff can check in attendees.
     */
    public function checkIn(User $user, Event $event): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $event->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
