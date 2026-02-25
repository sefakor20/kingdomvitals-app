<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Tenant\Member;
use App\Services\AI\DTOs\ChurnRiskAssessment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DonorChurnService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Get the relationships that should be eager loaded for churn scoring.
     *
     * @return array<int, string>
     */
    public static function memberEagerLoads(): array
    {
        return ['donations', 'attendance'];
    }

    /**
     * Calculate churn risk score for a member.
     */
    public function calculateScore(Member $member): ChurnRiskAssessment
    {
        return $this->calculateHeuristicScore($member);
    }

    /**
     * Calculate score using heuristic algorithm.
     */
    protected function calculateHeuristicScore(Member $member): ChurnRiskAssessment
    {
        $config = config('ai.scoring.churn');
        $factors = [];

        // Get donation history
        $donations = $member->donations()
            ->orderBy('donation_date', 'desc')
            ->get();

        if ($donations->isEmpty()) {
            // No donation history - not a donor, low churn risk (nothing to churn from)
            return new ChurnRiskAssessment(
                score: 0,
                factors: ['no_donation_history' => ['description' => 'No donation history']],
                daysSinceLastDonation: null,
                provider: 'heuristic',
                model: 'v1',
            );
        }

        $score = $config['base_score'];

        // Factor 1: Days since last donation
        $lastDonation = $donations->first();
        $daysSinceLastDonation = Carbon::parse($lastDonation->donation_date)->diffInDays(now());

        // Calculate typical donation interval
        $typicalInterval = $this->calculateTypicalInterval($donations);

        // If they've exceeded their typical interval significantly, increase risk
        if ($typicalInterval > 0) {
            $intervalRatio = $daysSinceLastDonation / $typicalInterval;

            if ($intervalRatio > 2) {
                // More than double their typical interval
                $intervalPenalty = min(($intervalRatio - 1) * 15, 40);
                $score += $intervalPenalty;
                $factors['exceeded_interval'] = [
                    'value' => round($intervalRatio, 1),
                    'impact' => $intervalPenalty,
                    'description' => "At {$intervalRatio}x typical giving interval",
                ];
            }
        }

        // Direct days-based risk
        if ($daysSinceLastDonation > 90) {
            $daysRisk = min(($daysSinceLastDonation - 90) / 30 * 10, 30);
            $score += $daysRisk;
            $factors['days_inactive'] = [
                'value' => $daysSinceLastDonation,
                'impact' => $daysRisk,
                'description' => "{$daysSinceLastDonation} days since last donation",
            ];
        }

        // Factor 2: Giving trend (compare last 3 months to previous 3 months)
        $givingTrend = $this->calculateGivingTrend($donations);
        if ($givingTrend < -30) {
            // Significant decline in giving
            $trendPenalty = min(abs($givingTrend) / 100 * 20, 20);
            $score += $trendPenalty;
            $factors['declining_trend'] = [
                'value' => round($givingTrend, 1),
                'impact' => $trendPenalty,
                'description' => 'Giving has declined '.abs(round($givingTrend)).'%',
            ];
        } elseif ($givingTrend > 20) {
            // Increasing giving - reduce risk
            $trendBonus = min($givingTrend / 100 * 15, 15);
            $score -= $trendBonus;
            $factors['increasing_trend'] = [
                'value' => round($givingTrend, 1),
                'impact' => -$trendBonus,
                'description' => 'Giving has increased '.round($givingTrend).'%',
            ];
        }

        // Factor 3: Attendance correlation
        $recentAttendance = $member->attendance()
            ->where('date', '>=', now()->subMonths(3))
            ->count();

        if ($recentAttendance === 0 && $daysSinceLastDonation > 30) {
            // No attendance and no recent donations - high risk
            $attendancePenalty = 15;
            $score += $attendancePenalty;
            $factors['no_recent_attendance'] = [
                'value' => 0,
                'impact' => $attendancePenalty,
                'description' => 'No attendance in last 3 months',
            ];
        }

        // Factor 4: Donation frequency
        $donationCount = $donations->count();
        if ($donationCount >= 12) {
            // Regular donor - if they stop, it's more significant
            $score += 5;
            $factors['regular_donor'] = [
                'value' => $donationCount,
                'impact' => 5,
                'description' => 'Historically regular donor ({$donationCount} donations)',
            ];
        }

        // Ensure score stays within 0-100 range
        $score = max(0, min(100, $score));

        return new ChurnRiskAssessment(
            score: round($score, 2),
            factors: $factors,
            daysSinceLastDonation: (int) $daysSinceLastDonation,
            provider: 'heuristic',
            model: 'v1',
        );
    }

    /**
     * Calculate typical donation interval in days.
     */
    protected function calculateTypicalInterval(Collection $donations): float
    {
        if ($donations->count() < 2) {
            return 30; // Default to monthly
        }

        $intervals = [];
        $dates = $donations->pluck('donation_date')->map(fn (\DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $d): \Illuminate\Support\Carbon => Carbon::parse($d))->values();

        for ($i = 0; $i < min($dates->count() - 1, 10); $i++) {
            $intervals[] = $dates[$i]->diffInDays($dates[$i + 1]);
        }

        if ($intervals === []) {
            return 30;
        }

        return array_sum($intervals) / count($intervals);
    }

    /**
     * Calculate giving trend (percentage change).
     */
    protected function calculateGivingTrend(Collection $donations): float
    {
        $threeMonthsAgo = now()->subMonths(3);
        $sixMonthsAgo = now()->subMonths(6);

        $recentGiving = $donations
            ->filter(fn ($d): bool => Carbon::parse($d->donation_date)->gte($threeMonthsAgo))
            ->sum('amount');

        $previousGiving = $donations
            ->filter(fn ($d): bool => Carbon::parse($d->donation_date)->gte($sixMonthsAgo) && Carbon::parse($d->donation_date)->lt($threeMonthsAgo))
            ->sum('amount');

        if ($previousGiving == 0) {
            return $recentGiving > 0 ? 100 : 0;
        }

        return (($recentGiving - $previousGiving) / $previousGiving) * 100;
    }

    /**
     * Get members at high churn risk.
     */
    public function getAtRiskDonors(string $branchId, int $limit = 20): Collection
    {
        return Member::where('primary_branch_id', $branchId)
            ->where('churn_risk_score', '>', 70)
            ->orderByDesc('churn_risk_score')
            ->limit($limit)
            ->get();
    }
}
