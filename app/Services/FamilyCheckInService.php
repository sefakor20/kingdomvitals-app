<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;

class FamilyCheckInService
{
    /**
     * Check in multiple family members at once.
     *
     * @param  array<string>  $memberIds  Array of member UUIDs to check in
     * @return Collection<int, Attendance>
     */
    public function checkInFamily(
        Household $household,
        Service $service,
        Branch $branch,
        array $memberIds,
        CheckInMethod $method = CheckInMethod::Kiosk
    ): Collection {
        $checkedIn = collect();
        $date = now()->toDateString();
        $time = now()->toTimeString();

        foreach ($memberIds as $memberId) {
            $member = Member::where('id', $memberId)
                ->where('household_id', $household->id)
                ->first();

            if (! $member) {
                continue;
            }

            // Skip if already checked in today for this service
            $existing = Attendance::where('service_id', $service->id)
                ->where('date', $date)
                ->where('member_id', $member->id)
                ->exists();

            if ($existing) {
                continue;
            }

            $attendance = Attendance::create([
                'service_id' => $service->id,
                'branch_id' => $branch->id,
                'date' => $date,
                'member_id' => $member->id,
                'check_in_time' => $time,
                'check_in_method' => $method,
            ]);

            $checkedIn->push($attendance);
        }

        return $checkedIn;
    }

    /**
     * Check in a child with security code generation.
     */
    public function checkInChildWithSecurity(
        Member $child,
        ?Member $guardian,
        Service $service,
        Branch $branch,
        CheckInMethod $method = CheckInMethod::Kiosk
    ): ChildrenCheckinSecurity {
        $date = now()->toDateString();
        $time = now()->toTimeString();

        // Create the attendance record
        $attendance = Attendance::create([
            'service_id' => $service->id,
            'branch_id' => $branch->id,
            'date' => $date,
            'member_id' => $child->id,
            'check_in_time' => $time,
            'check_in_method' => $method,
        ]);

        // Generate security code
        $securityCode = ChildrenCheckinSecurity::generateSecurityCode();

        // Create security record
        return ChildrenCheckinSecurity::create([
            'attendance_id' => $attendance->id,
            'child_member_id' => $child->id,
            'guardian_member_id' => $guardian?->id,
            'security_code' => $securityCode,
        ]);
    }

    /**
     * Get all members of a household that can be checked in.
     */
    public function getHouseholdMembers(Household $household): Collection
    {
        return Member::where('household_id', $household->id)
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN household_role = 'head' THEN 1 WHEN household_role = 'spouse' THEN 2 WHEN household_role = 'child' THEN 3 ELSE 4 END")
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Check if any member of the household is already checked in.
     *
     * @return Collection<int, Member>
     */
    public function getAlreadyCheckedInMembers(
        Household $household,
        Service $service,
        string $date
    ): Collection {
        $memberIds = Member::where('household_id', $household->id)->pluck('id');

        return Member::whereIn('id', function ($query) use ($service, $date, $memberIds) {
            $query->select('member_id')
                ->from('attendance')
                ->where('service_id', $service->id)
                ->where('date', $date)
                ->whereIn('member_id', $memberIds);
        })->get();
    }

    /**
     * Verify a security code for child checkout.
     */
    public function verifySecurityCode(
        string $code,
        Service $service,
        string $date
    ): ?ChildrenCheckinSecurity {
        return ChildrenCheckinSecurity::where('security_code', $code)
            ->where('is_checked_out', false)
            ->whereHas('attendance', function ($query) use ($service, $date) {
                $query->where('service_id', $service->id)
                    ->where('date', $date);
            })
            ->first();
    }
}
