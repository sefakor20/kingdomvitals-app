<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\EmploymentStatus;
use App\Enums\LifecycleStage;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\GivingCapacityAssessment;
use App\Services\AI\DTOs\GivingTrend;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GivingCapacityService
{
    /**
     * Profession to estimated income bracket mapping.
     * Values represent estimated annual income capacity for giving (10-15% tithe potential).
     *
     * @var array<string, float>
     */
    protected array $professionCapacity = [
        'doctor' => 15000,
        'physician' => 15000,
        'lawyer' => 12000,
        'attorney' => 12000,
        'engineer' => 10000,
        'software' => 10000,
        'developer' => 9000,
        'manager' => 8000,
        'director' => 10000,
        'executive' => 15000,
        'ceo' => 20000,
        'accountant' => 7000,
        'nurse' => 6000,
        'teacher' => 5000,
        'lecturer' => 6000,
        'professor' => 8000,
        'banker' => 8000,
        'pharmacist' => 8000,
        'architect' => 9000,
        'consultant' => 9000,
        'pilot' => 12000,
        'dentist' => 12000,
        'entrepreneur' => 10000,
        'business' => 8000,
        'trader' => 6000,
        'farmer' => 4000,
        'pastor' => 5000,
        'minister' => 5000,
        'retired' => 3000,
        'student' => 500,
        'unemployed' => 500,
    ];

    public function __construct(
        protected AiService $aiService,
        protected GivingTrendService $givingTrendService
    ) {}

    /**
     * Assess giving capacity for a single member.
     */
    public function assessCapacity(Member $member): GivingCapacityAssessment
    {
        $factors = [];
        $estimatedCapacity = 0;

        // Factor 1: Profession-based capacity estimate
        $professionCapacity = $this->estimateProfessionCapacity($member);
        $estimatedCapacity = $professionCapacity['estimate'];
        if ($professionCapacity['estimate'] > 0) {
            $factors['profession'] = $professionCapacity;
        }

        // Factor 2: Employment status adjustment
        $employmentAdjustment = $this->calculateEmploymentAdjustment($member);
        $estimatedCapacity *= $employmentAdjustment['multiplier'];
        $factors['employment_status'] = $employmentAdjustment;

        // Factor 3: Historical giving trajectory
        $givingTrend = $this->givingTrendService->analyzeForMember($member, 12);
        $currentAnnualGiving = $givingTrend->totalGiven;

        $trajectoryFactor = $this->calculateTrajectoryFactor($givingTrend);
        $factors['giving_trajectory'] = $trajectoryFactor;

        // Factor 4: Lifecycle stage consideration
        $lifecycleAdjustment = $this->calculateLifecycleAdjustment($member);
        $estimatedCapacity *= $lifecycleAdjustment['multiplier'];
        $factors['lifecycle_stage'] = $lifecycleAdjustment;

        // Factor 5: Household context
        $householdFactor = $this->calculateHouseholdFactor($member);
        if ($householdFactor['adjustment'] !== 0) {
            $estimatedCapacity *= (1 + $householdFactor['adjustment']);
            $factors['household'] = $householdFactor;
        }

        // Calculate capacity score (how much of potential is being utilized)
        $capacityScore = $estimatedCapacity > 0
            ? min(100, ($currentAnnualGiving / $estimatedCapacity) * 100)
            : 0;

        // Calculate potential gap
        $potentialGap = max(0, $estimatedCapacity - $currentAnnualGiving);

        return new GivingCapacityAssessment(
            memberId: $member->id,
            capacityScore: round($capacityScore, 2),
            currentAnnualGiving: round($currentAnnualGiving, 2),
            potentialGap: round($potentialGap, 2),
            factors: $factors,
            provider: 'heuristic',
        );
    }

    /**
     * Estimate capacity based on profession.
     *
     * @return array{estimate: float, profession: ?string, matched_keyword: ?string, description: string}
     */
    protected function estimateProfessionCapacity(Member $member): array
    {
        $profession = strtolower(trim($member->profession ?? ''));

        if (empty($profession)) {
            return [
                'estimate' => 5000, // Default middle estimate
                'profession' => null,
                'matched_keyword' => null,
                'description' => 'No profession specified, using default estimate',
            ];
        }

        // Find matching profession keyword
        foreach ($this->professionCapacity as $keyword => $capacity) {
            if (str_contains($profession, $keyword)) {
                return [
                    'estimate' => $capacity,
                    'profession' => $member->profession,
                    'matched_keyword' => $keyword,
                    'description' => sprintf('Profession "%s" estimated at annual capacity %s', $member->profession, number_format($capacity)),
                ];
            }
        }

        // Default for unrecognized professions
        return [
            'estimate' => 5000,
            'profession' => $member->profession,
            'matched_keyword' => null,
            'description' => 'Profession not in database, using default estimate',
        ];
    }

    /**
     * Calculate employment status adjustment.
     *
     * @return array{multiplier: float, status: ?string, description: string}
     */
    protected function calculateEmploymentAdjustment(Member $member): array
    {
        $status = $member->employment_status;

        if ($status === null) {
            return [
                'multiplier' => 1.0,
                'status' => null,
                'description' => 'Employment status unknown',
            ];
        }

        $multiplier = match ($status) {
            EmploymentStatus::Employed => 1.0,
            EmploymentStatus::SelfEmployed => 1.1, // Often higher earning potential
            EmploymentStatus::Retired => 0.6,
            EmploymentStatus::Student => 0.2,
            EmploymentStatus::Unemployed => 0.1,
            default => 1.0,
        };

        return [
            'multiplier' => $multiplier,
            'status' => $status->value,
            'description' => sprintf('%s adjustment: %.0f%%', $status->value, $multiplier * 100),
        ];
    }

    /**
     * Calculate giving trajectory factor.
     *
     * @return array{trend: string, growth_rate: float, consistency: float, description: string}
     */
    protected function calculateTrajectoryFactor(GivingTrend $trend): array
    {
        return [
            'trend' => $trend->trend,
            'growth_rate' => $trend->growthRate,
            'consistency' => $trend->consistencyScore,
            'description' => sprintf(
                '%s trend with %.0f%% growth rate and %.0f%% consistency',
                ucfirst($trend->trend),
                $trend->growthRate,
                $trend->consistencyScore
            ),
        ];
    }

    /**
     * Calculate lifecycle stage adjustment.
     *
     * @return array{multiplier: float, stage: ?string, description: string}
     */
    protected function calculateLifecycleAdjustment(Member $member): array
    {
        $stage = $member->lifecycle_stage ?? LifecycleStage::Growing;

        $multiplier = match ($stage) {
            LifecycleStage::Engaged => 1.1, // Fully committed, may give more
            LifecycleStage::Growing => 1.0,
            LifecycleStage::NewMember => 0.8, // Still building connection
            LifecycleStage::Prospect => 0.5,
            LifecycleStage::Disengaging => 0.7,
            LifecycleStage::AtRisk, LifecycleStage::Dormant => 0.5,
            LifecycleStage::Inactive => 0.2,
        };

        return [
            'multiplier' => $multiplier,
            'stage' => $stage->value,
            'description' => sprintf('%s stage adjustment: %.0f%%', $stage->label(), $multiplier * 100),
        ];
    }

    /**
     * Calculate household factor adjustment.
     *
     * @return array{adjustment: float, is_head: bool, household_size: int, description: string}
     */
    protected function calculateHouseholdFactor(Member $member): array
    {
        $household = $member->household;

        if (! $household) {
            return [
                'adjustment' => 0,
                'is_head' => false,
                'household_size' => 1,
                'description' => 'Not part of a household',
            ];
        }

        $householdSize = $household->members()->count();
        $isHead = $member->household_role?->isHead() ?? false;

        // Heads of household may have higher giving responsibility
        // Larger households may have less disposable income
        $adjustment = 0;
        if ($isHead) {
            $adjustment += 0.1; // 10% boost for heads
        }
        if ($householdSize > 4) {
            $adjustment -= 0.1; // 10% reduction for large families
        }

        return [
            'adjustment' => $adjustment,
            'is_head' => $isHead,
            'household_size' => $householdSize,
            'description' => sprintf(
                'Household of %d (%s)',
                $householdSize,
                $isHead ? 'head' : 'member'
            ),
        ];
    }

    /**
     * Assess capacity for all members in a branch.
     *
     * @return Collection<GivingCapacityAssessment>
     */
    public function assessForBranch(Branch $branch): Collection
    {
        $members = Member::query()
            ->where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereHas('donations')
            ->get();

        return $members->map(fn (Member $member) => $this->assessCapacity($member));
    }

    /**
     * Save capacity assessments to members.
     *
     * @param  Collection<GivingCapacityAssessment>  $assessments
     */
    public function saveAssessments(Collection $assessments): int
    {
        $saved = 0;

        foreach ($assessments as $assessment) {
            try {
                Member::where('id', $assessment->memberId)->update([
                    'giving_capacity_score' => $assessment->capacityScore,
                    'giving_potential_gap' => $assessment->potentialGap,
                    'giving_capacity_factors' => $assessment->factors,
                    'giving_capacity_analyzed_at' => now(),
                ]);
                $saved++;
            } catch (\Throwable $e) {
                Log::warning('GivingCapacityService: Failed to save assessment', [
                    'member_id' => $assessment->memberId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $saved;
    }

    /**
     * Get members with highest untapped potential.
     *
     * @return Collection<Member>
     */
    public function getHighPotentialMembers(Branch $branch, int $limit = 50): Collection
    {
        return Member::query()
            ->where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('giving_capacity_score')
            ->where('giving_capacity_score', '<', 60) // Less than 60% utilized
            ->where('giving_potential_gap', '>', 0)
            ->orderByDesc('giving_potential_gap')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if the feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.giving_capacity.enabled', false);
    }
}
