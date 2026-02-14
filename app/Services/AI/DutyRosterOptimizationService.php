<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\ExperienceLevel;
use App\Enums\PlanModule;
use App\Models\Tenant\DutyRosterPool;
use App\Models\Tenant\DutyRosterPoolMember;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberUnavailability;
use App\Services\AI\DTOs\MemberSuitabilityScore;
use App\Services\AI\DTOs\RosterOptimizationResult;
use App\Services\PlanAccessService;
use Carbon\Carbon;

class DutyRosterOptimizationService
{
    public function __construct(
        protected AiService $aiService,
    ) {}

    /**
     * Check if roster optimization is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.roster_optimization.enabled', true)
            && app(PlanAccessService::class)->hasModule(PlanModule::AiInsights);
    }

    /**
     * Calculate suitability score for a member for a specific assignment.
     */
    public function calculateMemberSuitability(
        DutyRosterPoolMember $poolMember,
        Carbon $date,
        array $context = []
    ): MemberSuitabilityScore {
        $member = $poolMember->member;
        $config = config('ai.scoring.roster', []);

        $baseScore = $config['base_score'] ?? 50;
        $factors = [];
        $warnings = [];

        // Check availability
        $isAvailable = ! MemberUnavailability::isMemberUnavailable(
            $member->id,
            $poolMember->pool->branch_id,
            $date
        );

        if (! $isAvailable) {
            $warnings[] = 'Member is marked unavailable for this date';
        }

        // Check if already assigned to another role on this date
        if (isset($context['assigned_member_ids']) && in_array($member->id, $context['assigned_member_ids'])) {
            $warnings[] = 'Member already assigned to another role';
            $factors['conflict_penalty'] = -($config['conflict_penalty'] ?? 30);
        }

        // 1. Fairness bonus - lower assignment count gets higher bonus
        $maxAssignments = $context['max_assignment_count'] ?? 10;
        $assignmentCount = $poolMember->assignment_count ?? 0;
        $fairnessRatio = $maxAssignments > 0
            ? max(0, 1 - ($assignmentCount / $maxAssignments))
            : 1;
        $fairnessBonus = $fairnessRatio * ($config['fairness_max_bonus'] ?? 20);
        $factors['fairness'] = round($fairnessBonus, 2);

        // 2. Experience match - for roles that need experienced members
        $experienceLevel = ExperienceLevel::tryFrom($poolMember->experience_level ?? 'intermediate')
            ?? ExperienceLevel::Intermediate;
        $experienceBonus = $experienceLevel->priorityWeight();
        $factors['experience'] = $experienceBonus;

        // 3. Reliability score - based on historical performance
        $reliabilityScore = $poolMember->reliability_score ?? 50;
        $reliabilityBonus = ($reliabilityScore / 100) * ($config['reliability_max_bonus'] ?? 15);
        $factors['reliability'] = round($reliabilityBonus, 2);

        // 4. Recency bonus - longer since last assignment = higher bonus
        $lastAssigned = $poolMember->last_assigned_date;
        $recencyBonus = 0;
        if ($lastAssigned) {
            $daysSinceAssigned = Carbon::parse($lastAssigned)->diffInDays($date);
            $recencyBonus = min($config['recency_max_bonus'] ?? 10, $daysSinceAssigned * 0.5);
        } else {
            // Never assigned - full bonus
            $recencyBonus = $config['recency_max_bonus'] ?? 10;
        }
        $factors['recency'] = round($recencyBonus, 2);

        // 5. Check monthly workload cap
        if ($poolMember->max_monthly_assignments !== null) {
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $monthlyCount = $this->getMonthlyAssignmentCount($poolMember, $monthStart, $monthEnd);

            if ($monthlyCount >= $poolMember->max_monthly_assignments) {
                $warnings[] = 'Member has reached monthly assignment limit';
                $factors['overwork_penalty'] = -($config['overwork_penalty'] ?? 20);
            }
        }

        // 6. Preference match - if member prefers this service
        if (! empty($poolMember->preferred_service_ids) && isset($context['service_id'])) {
            if (in_array($context['service_id'], $poolMember->preferred_service_ids)) {
                $factors['preference'] = 5;
            }
        }

        // Calculate total score
        $totalScore = $baseScore + array_sum($factors);
        $totalScore = max(0, min(100, $totalScore));

        return new MemberSuitabilityScore(
            memberId: $member->id,
            memberName: $member->full_name,
            totalScore: $totalScore,
            factors: $factors,
            isAvailable: $isAvailable,
            warnings: $warnings,
            experienceLevel: $experienceLevel,
        );
    }

    /**
     * Get optimized assignments for pools on a specific date.
     *
     * @param  array<string, DutyRosterPool>  $pools  Keyed by role type
     */
    public function optimizeAssignments(
        array $pools,
        Carbon $date,
        ?string $serviceId = null
    ): RosterOptimizationResult {
        $assignments = [];
        $alternatives = [];
        $allWarnings = [];
        $factors = [];
        $assignedMemberIds = [];

        foreach ($pools as $roleType => $pool) {
            if (! $pool) {
                continue;
            }

            // Get all active pool members with scores
            $poolMembers = DutyRosterPoolMember::query()
                ->where('duty_roster_pool_id', $pool->id)
                ->where('is_active', true)
                ->with('member')
                ->get();

            if ($poolMembers->isEmpty()) {
                $allWarnings[] = "No active members in {$roleType} pool";

                continue;
            }

            // Calculate max assignment count for fairness calculation
            $maxAssignmentCount = $poolMembers->max('assignment_count') ?: 1;

            // Score all members
            $scores = $poolMembers->map(function ($pm) use ($date, $assignedMemberIds, $maxAssignmentCount, $serviceId) {
                return $this->calculateMemberSuitability($pm, $date, [
                    'assigned_member_ids' => $assignedMemberIds,
                    'max_assignment_count' => $maxAssignmentCount,
                    'service_id' => $serviceId,
                ]);
            });

            // Sort by total score descending, filter to available members
            $availableScores = $scores
                ->filter(fn (MemberSuitabilityScore $s) => $s->isAvailable)
                ->sortByDesc(fn (MemberSuitabilityScore $s) => $s->totalScore)
                ->values();

            if ($availableScores->isEmpty()) {
                $allWarnings[] = "No available members for {$roleType} on this date";

                continue;
            }

            // Best match is first
            $bestMatch = $availableScores->first();
            $assignments[$roleType] = $bestMatch;
            $assignedMemberIds[] = $bestMatch->memberId;

            // Add warnings from the selected member
            foreach ($bestMatch->warnings as $warning) {
                $allWarnings[] = "[{$roleType}] {$warning}";
            }

            // Store alternatives (next 3 best matches)
            $alternatives[$roleType] = $availableScores->slice(1, 3)->values()->all();

            // Track per-role optimization quality
            $factors[$roleType] = [
                'score' => $bestMatch->totalScore,
                'alternatives_count' => count($alternatives[$roleType]),
            ];
        }

        // Calculate overall optimization score
        $roleScores = array_column($factors, 'score');
        $optimizationScore = count($roleScores) > 0
            ? array_sum($roleScores) / count($roleScores)
            : 0;

        return new RosterOptimizationResult(
            assignments: $assignments,
            optimizationScore: $optimizationScore,
            factors: $factors,
            warnings: $allWarnings,
            alternatives: $alternatives,
        );
    }

    /**
     * Detect conflicts in proposed assignments.
     */
    public function detectConflicts(array $proposedAssignments, Carbon $date): array
    {
        $conflicts = [];
        $memberIds = [];

        foreach ($proposedAssignments as $memberId) {
            if ($memberId === null) {
                continue;
            }

            // Check for duplicate assignments
            if (in_array($memberId, $memberIds)) {
                $conflicts[] = 'Same member assigned to multiple roles';
            }
            $memberIds[] = $memberId;

            // Check availability
            $member = Member::find($memberId);
            if ($member && MemberUnavailability::isMemberUnavailable($memberId, $member->primary_branch_id, $date)) {
                $conflicts[] = "{$member->full_name} is marked unavailable for this date";
            }
        }

        return $conflicts;
    }

    /**
     * Update all pool member scores for a pool.
     */
    public function updatePoolMemberScores(DutyRosterPool $pool): int
    {
        $poolMembers = DutyRosterPoolMember::query()
            ->where('duty_roster_pool_id', $pool->id)
            ->get();

        $updated = 0;

        foreach ($poolMembers as $poolMember) {
            $reliabilityScore = $this->calculateReliabilityScore($poolMember);
            $skillScore = $this->calculateSkillScore($poolMember);

            $poolMember->update([
                'reliability_score' => $reliabilityScore,
                'skill_score' => $skillScore,
                'scores_calculated_at' => now(),
            ]);

            $updated++;
        }

        return $updated;
    }

    /**
     * Calculate reliability score based on assignment history.
     * For now, returns a base score. Can be enhanced with actual duty attendance tracking.
     */
    public function calculateReliabilityScore(DutyRosterPoolMember $poolMember): float
    {
        // Base reliability - assume good unless we have data suggesting otherwise
        $baseReliability = 75;

        // Bonus for experience
        $experienceLevel = ExperienceLevel::tryFrom($poolMember->experience_level ?? 'intermediate')
            ?? ExperienceLevel::Intermediate;
        $experienceBonus = $experienceLevel->level() * 5;

        // Bonus for consistent assignment history
        $assignmentCount = $poolMember->assignment_count ?? 0;
        $consistencyBonus = min(10, $assignmentCount);

        return min(100, $baseReliability + $experienceBonus + $consistencyBonus);
    }

    /**
     * Calculate skill score based on experience level.
     */
    public function calculateSkillScore(DutyRosterPoolMember $poolMember): float
    {
        $experienceLevel = ExperienceLevel::tryFrom($poolMember->experience_level ?? 'intermediate')
            ?? ExperienceLevel::Intermediate;

        return match ($experienceLevel) {
            ExperienceLevel::Novice => 40,
            ExperienceLevel::Intermediate => 60,
            ExperienceLevel::Experienced => 80,
            ExperienceLevel::Expert => 95,
        };
    }

    /**
     * Get the count of assignments for a member in a given month.
     */
    protected function getMonthlyAssignmentCount(
        DutyRosterPoolMember $poolMember,
        Carbon $monthStart,
        Carbon $monthEnd
    ): int {
        // This would require tracking individual assignments in a history table
        // For now, we'll estimate based on assignment_count and last_assigned_date
        if (! $poolMember->last_assigned_date) {
            return 0;
        }

        $lastAssigned = Carbon::parse($poolMember->last_assigned_date);

        if ($lastAssigned->between($monthStart, $monthEnd)) {
            // Rough estimate: assume 1 assignment per week on average
            $weeksSinceStart = $monthStart->diffInWeeks($lastAssigned) + 1;

            return min($weeksSinceStart, 4);
        }

        return 0;
    }

    /**
     * Batch update scores for all pools in a branch.
     */
    public function updateAllPoolScores(string $branchId): int
    {
        $pools = DutyRosterPool::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $totalUpdated = 0;

        foreach ($pools as $pool) {
            $totalUpdated += $this->updatePoolMemberScores($pool);
        }

        return $totalUpdated;
    }
}
