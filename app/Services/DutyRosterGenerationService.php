<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DutyRosterStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\DutyRosterPool;
use App\Models\Tenant\DutyRosterPoolMember;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberUnavailability;
use App\Models\Tenant\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DutyRosterGenerationService
{
    /**
     * Generate dates for a service between start and end dates.
     *
     * @return Collection<int, Carbon>
     */
    public function generateDatesForService(Service $service, Carbon $startDate, Carbon $endDate): Collection
    {
        $dates = collect();
        $dayOfWeek = $service->day_of_week;

        if ($dayOfWeek === null) {
            return $dates;
        }

        // Start from the first occurrence of the day_of_week on or after startDate
        $current = $startDate->copy();
        while ($current->dayOfWeek !== $dayOfWeek) {
            $current->addDay();
        }

        // Collect all matching dates within the range
        while ($current->lte($endDate)) {
            $dates->push($current->copy());
            $current->addWeek();
        }

        return $dates;
    }

    /**
     * Generate dates for specific days of the week between start and end dates.
     *
     * @param  array<int>  $daysOfWeek  Array of day numbers (0=Sunday, 6=Saturday)
     * @return Collection<int, Carbon>
     */
    public function generateDatesForDays(array $daysOfWeek, Carbon $startDate, Carbon $endDate): Collection
    {
        $dates = collect();

        foreach ($daysOfWeek as $dayOfWeek) {
            $current = $startDate->copy();

            // Find the first occurrence of this day on or after startDate
            while ($current->dayOfWeek !== $dayOfWeek) {
                $current->addDay();
            }

            // Collect all matching dates within the range
            while ($current->lte($endDate)) {
                $dates->push($current->copy());
                $current->addWeek();
            }
        }

        return $dates->sortBy(fn (Carbon $date) => $date->timestamp)->values();
    }

    /**
     * Get the next available member from a pool for a specific date.
     * Implements round-robin selection with unavailability checking.
     */
    public function getNextAvailableMember(
        DutyRosterPool $pool,
        Carbon $date,
        ?Collection $unavailableMemberIds = null
    ): ?Member {
        // Get unavailable member IDs for this date if not provided
        if (!$unavailableMemberIds instanceof \Illuminate\Support\Collection) {
            $unavailableMemberIds = MemberUnavailability::query()
                ->where('branch_id', $pool->branch_id)
                ->whereDate('unavailable_date', $date)
                ->pluck('member_id');
        }

        // Get active pool members sorted by round-robin criteria
        // Priority: lowest assignment_count first, then earliest last_assigned_date
        $poolMember = DutyRosterPoolMember::query()
            ->where('duty_roster_pool_id', $pool->id)
            ->where('is_active', true)
            ->whereNotIn('member_id', $unavailableMemberIds)
            ->orderBy('assignment_count', 'asc')
            ->orderByRaw('last_assigned_date IS NULL DESC') // NULLs first
            ->orderBy('last_assigned_date', 'asc')
            ->orderBy('sort_order', 'asc')
            ->first();

        return $poolMember?->member;
    }

    /**
     * Preview what would be generated without persisting.
     *
     * @param  array{
     *     service_id?: string|null,
     *     days_of_week?: array<int>,
     *     start_date: string,
     *     end_date: string,
     *     preacher_pool_id?: string|null,
     *     liturgist_pool_id?: string|null,
     *     reader_pool_id?: string|null,
     * }  $config
     * @return array<int, array{
     *     date: Carbon,
     *     service: Service|null,
     *     preacher: Member|null,
     *     liturgist: Member|null,
     *     reader: Member|null,
     *     conflicts: array<string>,
     * }>
     */
    public function previewGeneration(Branch $branch, array $config): array
    {
        $startDate = Carbon::parse($config['start_date']);
        $endDate = Carbon::parse($config['end_date']);

        // Determine dates to generate for
        $dates = collect();
        $service = null;

        if (! empty($config['service_id'])) {
            $service = Service::find($config['service_id']);
            if ($service) {
                $dates = $this->generateDatesForService($service, $startDate, $endDate);
            }
        } elseif (! empty($config['days_of_week'])) {
            $dates = $this->generateDatesForDays($config['days_of_week'], $startDate, $endDate);
        }

        // Load pools
        $preacherPool = empty($config['preacher_pool_id'])
            ? null
            : DutyRosterPool::find($config['preacher_pool_id']);
        $liturgistPool = empty($config['liturgist_pool_id'])
            ? null
            : DutyRosterPool::find($config['liturgist_pool_id']);
        $readerPool = empty($config['reader_pool_id'])
            ? null
            : DutyRosterPool::find($config['reader_pool_id']);

        // Get all unavailabilities in the date range
        $unavailabilities = MemberUnavailability::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('unavailable_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($u) => $u->unavailable_date->format('Y-m-d'));

        // Get existing rosters to check for conflicts
        $existingRosters = DutyRoster::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('service_date', [$startDate, $endDate])
            ->when($service, fn ($q) => $q->where('service_id', $service->id))
            ->get()
            ->keyBy(fn ($r) => $r->service_date->format('Y-m-d'));

        // Track assignments for round-robin simulation
        $simulatedAssignments = [
            'preacher' => [],
            'liturgist' => [],
            'reader' => [],
        ];

        $preview = [];

        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $unavailableMemberIds = $unavailabilities->get($dateKey, collect())->pluck('member_id');

            $conflicts = [];

            // Check for existing roster
            if ($existingRosters->has($dateKey)) {
                $conflicts[] = 'A roster already exists for this date';
            }

            // Simulate round-robin assignment for each role
            $preacher = null;
            $liturgist = null;
            $reader = null;

            if ($preacherPool) {
                $preacher = $this->simulateNextMember(
                    $preacherPool,
                    $unavailableMemberIds,
                    $simulatedAssignments['preacher']
                );
            }

            if ($liturgistPool) {
                $liturgist = $this->simulateNextMember(
                    $liturgistPool,
                    $unavailableMemberIds,
                    $simulatedAssignments['liturgist']
                );
            }

            if ($readerPool) {
                $reader = $this->simulateNextMember(
                    $readerPool,
                    $unavailableMemberIds,
                    $simulatedAssignments['reader']
                );
            }

            $preview[] = [
                'date' => $date,
                'service' => $service,
                'preacher' => $preacher,
                'liturgist' => $liturgist,
                'reader' => $reader,
                'conflicts' => $conflicts,
            ];
        }

        return $preview;
    }

    /**
     * Generate and persist duty rosters.
     *
     * @param  array{
     *     service_id?: string|null,
     *     days_of_week?: array<int>,
     *     start_date: string,
     *     end_date: string,
     *     preacher_pool_id?: string|null,
     *     liturgist_pool_id?: string|null,
     *     reader_pool_id?: string|null,
     *     skip_existing?: bool,
     * }  $config
     * @return Collection<int, DutyRoster>
     */
    public function generateRosters(Branch $branch, array $config, ?string $createdBy = null): Collection
    {
        $startDate = Carbon::parse($config['start_date']);
        $endDate = Carbon::parse($config['end_date']);
        $skipExisting = $config['skip_existing'] ?? true;

        // Determine dates to generate for
        $dates = collect();
        $service = null;

        if (! empty($config['service_id'])) {
            $service = Service::find($config['service_id']);
            if ($service) {
                $dates = $this->generateDatesForService($service, $startDate, $endDate);
            }
        } elseif (! empty($config['days_of_week'])) {
            $dates = $this->generateDatesForDays($config['days_of_week'], $startDate, $endDate);
        }

        if ($dates->isEmpty()) {
            return collect();
        }

        // Load pools
        $preacherPool = empty($config['preacher_pool_id'])
            ? null
            : DutyRosterPool::find($config['preacher_pool_id']);
        $liturgistPool = empty($config['liturgist_pool_id'])
            ? null
            : DutyRosterPool::find($config['liturgist_pool_id']);
        $readerPool = empty($config['reader_pool_id'])
            ? null
            : DutyRosterPool::find($config['reader_pool_id']);

        // Get all unavailabilities in the date range
        $unavailabilities = MemberUnavailability::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('unavailable_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($u) => $u->unavailable_date->format('Y-m-d'));

        // Get existing roster dates if we need to skip them
        $existingDates = collect();
        if ($skipExisting) {
            $existingDates = DutyRoster::query()
                ->where('branch_id', $branch->id)
                ->whereBetween('service_date', [$startDate, $endDate])
                ->when($service, fn ($q) => $q->where('service_id', $service->id))
                ->pluck('service_date')
                ->map(fn ($d) => $d->format('Y-m-d'));
        }

        $generatedRosters = collect();

        DB::transaction(function () use (
            $dates,
            $existingDates,
            $branch,
            $service,
            $preacherPool,
            $liturgistPool,
            $readerPool,
            $unavailabilities,
            $createdBy,
            &$generatedRosters
        ): void {
            foreach ($dates as $date) {
                $dateKey = $date->format('Y-m-d');

                // Skip if roster already exists
                if ($existingDates->contains($dateKey)) {
                    continue;
                }

                $unavailableMemberIds = $unavailabilities->get($dateKey, collect())->pluck('member_id');

                // Get and assign members from pools
                $preacherId = null;
                $liturgistId = null;

                if ($preacherPool) {
                    $preacher = $this->getNextAvailableMember($preacherPool, $date, $unavailableMemberIds);
                    if ($preacher instanceof \App\Models\Tenant\Member) {
                        $preacherId = $preacher->id;
                        $this->recordAssignment($preacherPool, $preacher->id, $date);
                    }
                }

                if ($liturgistPool) {
                    $liturgist = $this->getNextAvailableMember($liturgistPool, $date, $unavailableMemberIds);
                    if ($liturgist instanceof \App\Models\Tenant\Member) {
                        $liturgistId = $liturgist->id;
                        $this->recordAssignment($liturgistPool, $liturgist->id, $date);
                    }
                }

                // Create the roster
                $roster = DutyRoster::create([
                    'branch_id' => $branch->id,
                    'service_id' => $service?->id,
                    'service_date' => $date,
                    'preacher_id' => $preacherId,
                    'liturgist_id' => $liturgistId,
                    'status' => DutyRosterStatus::Draft,
                    'created_by' => $createdBy,
                ]);

                // If we have a reader pool, add a scripture reading with the reader assigned
                if ($readerPool) {
                    $reader = $this->getNextAvailableMember($readerPool, $date, $unavailableMemberIds);
                    if ($reader instanceof \App\Models\Tenant\Member) {
                        $this->recordAssignment($readerPool, $reader->id, $date);
                        // Note: Scripture readings would need to be added separately
                        // as they require reference and reading_type
                    }
                }

                $generatedRosters->push($roster);
            }
        });

        return $generatedRosters;
    }

    /**
     * Record an assignment in the pool member's rotation tracking.
     */
    private function recordAssignment(DutyRosterPool $pool, string $memberId, Carbon $date): void
    {
        DutyRosterPoolMember::query()
            ->where('duty_roster_pool_id', $pool->id)
            ->where('member_id', $memberId)
            ->update([
                'last_assigned_date' => $date,
                'assignment_count' => DB::raw('assignment_count + 1'),
            ]);
    }

    /**
     * Simulate round-robin assignment for preview (doesn't persist).
     *
     * @param  array<string, int>  $simulatedAssignments  Tracks assignment counts per member
     */
    private function simulateNextMember(
        DutyRosterPool $pool,
        Collection $unavailableMemberIds,
        array &$simulatedAssignments
    ): ?Member {
        // Get all active pool members
        $poolMembers = DutyRosterPoolMember::query()
            ->where('duty_roster_pool_id', $pool->id)
            ->where('is_active', true)
            ->whereNotIn('member_id', $unavailableMemberIds)
            ->with('member')
            ->get();

        if ($poolMembers->isEmpty()) {
            return null;
        }

        // Sort by combined real + simulated assignment count
        $sorted = $poolMembers->sortBy(function ($pm) use ($simulatedAssignments): float|int|array {
            $realCount = $pm->assignment_count;
            $simulatedCount = $simulatedAssignments[$pm->member_id] ?? 0;

            return $realCount + $simulatedCount;
        })->values();

        $selected = $sorted->first();

        if ($selected) {
            // Track this simulated assignment
            $memberId = $selected->member_id;
            $simulatedAssignments[$memberId] = ($simulatedAssignments[$memberId] ?? 0) + 1;

            return $selected->member;
        }

        return null;
    }

    /**
     * Reset rotation counters for a pool.
     */
    public function resetPoolRotation(DutyRosterPool $pool): void
    {
        DutyRosterPoolMember::query()
            ->where('duty_roster_pool_id', $pool->id)
            ->update([
                'last_assigned_date' => null,
                'assignment_count' => 0,
            ]);
    }
}
