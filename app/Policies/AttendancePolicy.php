<?php

namespace App\Policies;

use App\Enums\BranchRole;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records for a branch.
     * All roles can view attendance.
     */
    public function viewAny(User $user, Branch $branch): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $branch->id)
            ->exists();
    }

    /**
     * Determine whether the user can view the attendance record.
     * All roles can view attendance in branches they have access to.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $attendance->branch_id)
            ->exists();
    }

    /**
     * Determine whether the user can create attendance records.
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
     * Determine whether the user can update the attendance record.
     * Admin, Manager, and Staff can update.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $attendance->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }

    /**
     * Determine whether the user can delete the attendance record.
     * Admin, Manager, and Staff can delete.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->branchAccess()
            ->where('branch_id', $attendance->branch_id)
            ->whereIn('role', [
                BranchRole::Admin->value,
                BranchRole::Manager->value,
                BranchRole::Staff->value,
            ])
            ->exists();
    }
}
