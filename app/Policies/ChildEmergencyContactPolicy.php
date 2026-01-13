<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildEmergencyContact;
use App\Models\User;

/**
 * Policy for Child Emergency Contact management in the Children module.
 */
class ChildEmergencyContactPolicy
{
    /**
     * Determine whether the user can view any emergency contacts for a branch.
     * All roles can view emergency contacts.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the emergency contact.
     * All roles can view emergency contacts in branches they have access to.
     */
    public function view(User $user, ChildEmergencyContact $contact): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $contact->member->primary_branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create emergency contacts.
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
     * Determine whether the user can update the emergency contact.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, ChildEmergencyContact $contact): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $contact->member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the emergency contact.
     * Admin and Manager can delete.
     */
    public function delete(User $user, ChildEmergencyContact $contact): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $contact->member->primary_branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
            ])
            ->exists();
    }
}
