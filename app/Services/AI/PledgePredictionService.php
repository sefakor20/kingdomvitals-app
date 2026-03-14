<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\PledgeStatus;
use App\Enums\RiskLevel;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgePrediction as PledgePredictionModel;
use App\Services\AI\DTOs\PledgeFulfillmentPrediction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PledgePredictionService
{
    public function __construct(
        protected AiService $aiService,
        protected GivingTrendService $givingTrendService
    ) {}

    /**
     * Predict fulfillment probability for a pledge.
     */
    public function predictFulfillment(Pledge $pledge): PledgeFulfillmentPrediction
    {
        $member = $pledge->member;
        $factors = [];
        $score = 50; // Base score

        // Factor 1: Current fulfillment pace
        $paceFactor = $this->calculatePaceFactor($pledge);
        $score += $paceFactor['score'];
        $factors['fulfillment_pace'] = $paceFactor;

        // Factor 2: Member's pledge history completion rate
        $historyFactor = $this->calculateHistoryFactor($member);
        $score += $historyFactor['score'];
        $factors['pledge_history'] = $historyFactor;

        // Factor 3: Member's giving trend
        $trendFactor = $this->calculateGivingTrendFactor($member);
        $score += $trendFactor['score'];
        $factors['giving_trend'] = $trendFactor;

        // Factor 4: Time remaining vs amount remaining
        $timeRemainingFactor = $this->calculateTimeRemainingFactor($pledge);
        $score += $timeRemainingFactor['score'];
        $factors['time_remaining'] = $timeRemainingFactor;

        // Factor 5: Days since last payment
        $recencyFactor = $this->calculateRecencyFactor($pledge, $member);
        $score += $recencyFactor['score'];
        $factors['payment_recency'] = $recencyFactor;

        // Ensure score stays within 0-100 range
        $score = max(0, min(100, $score));

        // Calculate risk level (inverse of probability)
        $riskLevel = RiskLevel::fromFulfillmentProbability($score);

        // Calculate recommended nudge date
        $recommendedNudgeAt = $this->calculateRecommendedNudgeDate($pledge, $riskLevel);

        return new PledgeFulfillmentPrediction(
            pledgeId: $pledge->id,
            memberId: $member->id,
            fulfillmentProbability: round($score, 2),
            riskLevel: $riskLevel,
            recommendedNudgeAt: $recommendedNudgeAt,
            factors: $factors,
            provider: 'heuristic',
        );
    }

    /**
     * Calculate pace factor based on current fulfillment vs expected.
     *
     * @return array{score: int, completion_percentage: float, expected_percentage: float, description: string}
     */
    protected function calculatePaceFactor(Pledge $pledge): array
    {
        $completionPercentage = $pledge->completionPercentage();

        // Calculate expected percentage based on time elapsed
        $startDate = Carbon::parse($pledge->start_date);
        $endDate = Carbon::parse($pledge->end_date);
        $now = now();

        if ($now >= $endDate) {
            $expectedPercentage = 100;
        } elseif ($now <= $startDate) {
            $expectedPercentage = 0;
        } else {
            $totalDays = $startDate->diffInDays($endDate);
            $elapsedDays = $startDate->diffInDays($now);
            $expectedPercentage = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;
        }

        // Calculate score based on how ahead or behind they are
        $paceRatio = $expectedPercentage > 0 ? $completionPercentage / $expectedPercentage : 1;

        $score = match (true) {
            $paceRatio >= 1.2 => 20,  // Ahead of schedule
            $paceRatio >= 1.0 => 15,  // On track
            $paceRatio >= 0.8 => 5,   // Slightly behind
            $paceRatio >= 0.5 => -10, // Significantly behind
            default => -20,           // Far behind
        };

        return [
            'score' => $score,
            'completion_percentage' => round($completionPercentage, 1),
            'expected_percentage' => round($expectedPercentage, 1),
            'description' => sprintf(
                '%.0f%% fulfilled (expected %.0f%%)',
                $completionPercentage,
                $expectedPercentage
            ),
        ];
    }

    /**
     * Calculate factor based on member's pledge history.
     *
     * @return array{score: int, completed: int, total: int, completion_rate: float, description: string}
     */
    protected function calculateHistoryFactor($member): array
    {
        $pastPledges = Pledge::where('member_id', $member->id)
            ->where('end_date', '<', now())
            ->get();

        $total = $pastPledges->count();

        if ($total === 0) {
            return [
                'score' => 0,
                'completed' => 0,
                'total' => 0,
                'completion_rate' => 0,
                'description' => 'No past pledge history',
            ];
        }

        $completed = $pastPledges->filter(function (Pledge $p) {
            return $p->completionPercentage() >= 90; // Consider 90%+ as completed
        })->count();

        $completionRate = ($completed / $total) * 100;

        $score = match (true) {
            $completionRate >= 90 => 20,
            $completionRate >= 70 => 10,
            $completionRate >= 50 => 0,
            $completionRate >= 30 => -10,
            default => -15,
        };

        return [
            'score' => $score,
            'completed' => $completed,
            'total' => $total,
            'completion_rate' => round($completionRate, 1),
            'description' => sprintf(
                '%d of %d past pledges completed (%.0f%%)',
                $completed,
                $total,
                $completionRate
            ),
        ];
    }

    /**
     * Calculate factor based on member's giving trend.
     *
     * @return array{score: int, trend: string, growth_rate: float, description: string}
     */
    protected function calculateGivingTrendFactor($member): array
    {
        $trend = $this->givingTrendService->analyzeForMember($member, 6);

        $score = match ($trend->trend) {
            'growing' => 15,
            'stable' => 5,
            'declining' => -10,
            'lapsed' => -20,
            default => 0,
        };

        // Adjust based on growth rate
        if ($trend->growthRate > 20) {
            $score += 5;
        } elseif ($trend->growthRate < -20) {
            $score -= 5;
        }

        return [
            'score' => $score,
            'trend' => $trend->trend,
            'growth_rate' => $trend->growthRate,
            'description' => sprintf(
                '%s giving trend (%.0f%% growth)',
                ucfirst($trend->trend),
                $trend->growthRate
            ),
        ];
    }

    /**
     * Calculate factor based on time remaining.
     *
     * @return array{score: int, days_remaining: int, amount_remaining: float, daily_requirement: float, description: string}
     */
    protected function calculateTimeRemainingFactor(Pledge $pledge): array
    {
        $endDate = Carbon::parse($pledge->end_date);
        $daysRemaining = max(0, (int) now()->diffInDays($endDate, false));
        $amountRemaining = $pledge->remainingAmount();

        if ($amountRemaining <= 0) {
            return [
                'score' => 25,
                'days_remaining' => $daysRemaining,
                'amount_remaining' => 0,
                'daily_requirement' => 0,
                'description' => 'Pledge already fulfilled',
            ];
        }

        if ($daysRemaining <= 0) {
            return [
                'score' => -25,
                'days_remaining' => 0,
                'amount_remaining' => $amountRemaining,
                'daily_requirement' => 0,
                'description' => sprintf('Pledge overdue with %.2f remaining', $amountRemaining),
            ];
        }

        $dailyRequirement = $amountRemaining / $daysRemaining;
        $monthlyRequirement = $dailyRequirement * 30;

        // Compare monthly requirement to member's average giving
        $score = match (true) {
            $daysRemaining > 90 => 10,  // Plenty of time
            $daysRemaining > 30 => 5,   // Good amount of time
            $daysRemaining > 14 => 0,   // Getting tight
            $daysRemaining > 7 => -10,  // Very tight
            default => -15,              // Critical
        };

        return [
            'score' => $score,
            'days_remaining' => $daysRemaining,
            'amount_remaining' => round($amountRemaining, 2),
            'daily_requirement' => round($dailyRequirement, 2),
            'description' => sprintf(
                '%d days to fulfill %.2f (%.2f/day)',
                $daysRemaining,
                $amountRemaining,
                $dailyRequirement
            ),
        ];
    }

    /**
     * Calculate factor based on days since last payment.
     *
     * @return array{score: int, days_since_payment: ?int, typical_interval: ?int, description: string}
     */
    protected function calculateRecencyFactor(Pledge $pledge, $member): array
    {
        // Get member's recent donations
        $lastDonation = $member->donations()
            ->latest('donation_date')
            ->first();

        if (! $lastDonation) {
            return [
                'score' => -15,
                'days_since_payment' => null,
                'typical_interval' => null,
                'description' => 'No donations recorded',
            ];
        }

        $daysSincePayment = Carbon::parse($lastDonation->donation_date)->diffInDays(now());

        // Calculate typical giving interval
        $donationCount = $member->donations()
            ->where('donation_date', '>=', now()->subMonths(6))
            ->count();
        $typicalInterval = $donationCount > 0 ? (int) (180 / $donationCount) : 60;

        $score = match (true) {
            $daysSincePayment <= $typicalInterval => 10,
            $daysSincePayment <= $typicalInterval * 1.5 => 0,
            $daysSincePayment <= $typicalInterval * 2 => -10,
            default => -15,
        };

        return [
            'score' => $score,
            'days_since_payment' => (int) $daysSincePayment,
            'typical_interval' => $typicalInterval,
            'description' => sprintf(
                '%d days since last payment (typical interval: %d days)',
                $daysSincePayment,
                $typicalInterval
            ),
        ];
    }

    /**
     * Calculate recommended nudge date based on risk level.
     */
    protected function calculateRecommendedNudgeDate(Pledge $pledge, RiskLevel $riskLevel): ?Carbon
    {
        if ($riskLevel === RiskLevel::Low) {
            return null; // No nudge needed
        }

        $config = config('ai.features.pledge_prediction', []);
        $nudgeBeforeDays = $config['nudge_before_due_days'] ?? 7;

        $endDate = Carbon::parse($pledge->end_date);
        $daysUntilEnd = now()->diffInDays($endDate, false);

        if ($daysUntilEnd <= 0) {
            // Already past due, nudge immediately
            return now();
        }

        if ($riskLevel === RiskLevel::High) {
            // Nudge sooner for high risk
            return now()->addDays(min(3, (int) ($daysUntilEnd / 2)));
        }

        // Medium risk - nudge before due date
        return now()->addDays(max(1, (int) ($daysUntilEnd - $nudgeBeforeDays)));
    }

    /**
     * Predict fulfillment for all active pledges in a branch.
     *
     * @return Collection<PledgeFulfillmentPrediction>
     */
    public function predictForBranch(Branch $branch): Collection
    {
        $pledges = Pledge::query()
            ->where('branch_id', $branch->id)
            ->where('status', PledgeStatus::Active)
            ->where('end_date', '>=', now())
            ->with('member')
            ->get();

        return $pledges->map(fn (Pledge $pledge) => $this->predictFulfillment($pledge));
    }

    /**
     * Save predictions to the database.
     *
     * @param  Collection<PledgeFulfillmentPrediction>  $predictions
     */
    public function savePredictions(Collection $predictions, Branch $branch): int
    {
        $saved = 0;

        foreach ($predictions as $prediction) {
            try {
                PledgePredictionModel::updateOrCreate(
                    [
                        'pledge_id' => $prediction->pledgeId,
                        'member_id' => $prediction->memberId,
                    ],
                    [
                        'branch_id' => $branch->id,
                        'fulfillment_probability' => $prediction->fulfillmentProbability,
                        'risk_level' => $prediction->riskLevel,
                        'recommended_nudge_at' => $prediction->recommendedNudgeAt,
                        'factors' => $prediction->factors,
                        'provider' => $prediction->provider,
                    ]
                );
                $saved++;
            } catch (\Throwable $e) {
                Log::warning('PledgePredictionService: Failed to save prediction', [
                    'pledge_id' => $prediction->pledgeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $saved;
    }

    /**
     * Get high-risk pledges that need attention.
     *
     * @return Collection<PledgePredictionModel>
     */
    public function getAtRiskPledges(Branch $branch, int $limit = 50): Collection
    {
        return PledgePredictionModel::query()
            ->where('branch_id', $branch->id)
            ->whereIn('risk_level', [RiskLevel::High, RiskLevel::Medium])
            ->orderBy('risk_level')
            ->orderBy('fulfillment_probability')
            ->limit($limit)
            ->with(['pledge', 'member'])
            ->get();
    }

    /**
     * Check if the feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.pledge_prediction.enabled', false);
    }
}
