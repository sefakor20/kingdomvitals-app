<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\DonationType;
use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\GivingTrend;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GivingTrendService
{
    /**
     * Analyze giving trends for a single member.
     */
    public function analyzeForMember(Member $member, int $months = 12): GivingTrend
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $endDate = now()->endOfDay();

        $donations = $member->donations()
            ->whereBetween('donation_date', [$startDate, $endDate])
            ->orderBy('donation_date', 'desc')
            ->get();

        // Handle members with no donations in the period
        if ($donations->isEmpty()) {
            return $this->createEmptyTrend($member, $months);
        }

        // Calculate all metrics
        $totalGiven = (float) $donations->sum('amount');
        $donationCount = $donations->count();
        $averageGift = $donationCount > 0 ? $totalGiven / $donationCount : 0;
        $largestGift = (float) ($donations->max('amount') ?? 0);

        $firstDonation = $donations->sortBy('donation_date')->first();
        $lastDonation = $donations->first();

        $firstDonationDate = Carbon::parse($firstDonation->donation_date);
        $lastDonationDate = Carbon::parse($lastDonation->donation_date);
        $daysSinceLastDonation = (int) $lastDonationDate->diffInDays(now());

        // Calculate donations per month
        $monthsActive = max(1, $firstDonationDate->diffInMonths(now()) + 1);
        $donationsPerMonth = $donationCount / $monthsActive;

        // Calculate monthly history
        $monthlyHistory = $this->calculateMonthlyHistory($donations, $months);

        // Calculate growth rate (compare last 6 months vs previous 6 months)
        $growthRate = $this->calculateGrowthRate($monthlyHistory);

        // Calculate consistency score
        $consistencyScore = $this->calculateConsistencyScore($donations, $monthlyHistory, $months);

        // Determine preferred type and method
        $preferredType = $this->getPreferredType($donations);
        $preferredMethod = $this->getPreferredMethod($donations);

        // Determine trend classification
        $trend = $this->classifyTrend($daysSinceLastDonation, $growthRate, $firstDonationDate);

        // Calculate confidence based on data quality
        $confidenceScore = $this->calculateConfidence($donationCount, $months);

        return new GivingTrend(
            memberId: $member->id,
            consistencyScore: round($consistencyScore, 1),
            growthRate: round($growthRate, 1),
            averageGift: round($averageGift, 2),
            totalGiven: round($totalGiven, 2),
            donationCount: $donationCount,
            donationsPerMonth: round($donationsPerMonth, 2),
            donorTier: 'bottom', // Will be calculated at branch level
            firstDonationDate: $firstDonationDate,
            lastDonationDate: $lastDonationDate,
            daysSinceLastDonation: $daysSinceLastDonation,
            largestGift: round($largestGift, 2),
            preferredType: $preferredType,
            preferredMethod: $preferredMethod,
            trend: $trend,
            monthlyHistory: $monthlyHistory,
            confidenceScore: $confidenceScore,
        );
    }

    /**
     * Analyze giving trends for all active members in a branch.
     *
     * @return Collection<int, GivingTrend>
     */
    public function analyzeForBranch(Branch $branch, int $months = 12): Collection
    {
        $config = config('ai.features.giving_trends', []);
        $historyMonths = $config['history_months'] ?? $months;

        // Get all members who have ever donated to this branch
        $memberIds = Donation::where('branch_id', $branch->id)
            ->whereNotNull('member_id')
            ->distinct()
            ->pluck('member_id');

        $members = Member::whereIn('id', $memberIds)
            ->where('status', MembershipStatus::Active)
            ->get();

        // Analyze each member
        $trends = $members->map(fn (Member $member): \App\Services\AI\DTOs\GivingTrend => $this->analyzeForMember($member, $historyMonths));

        // Calculate donor tiers based on branch totals
        return $this->assignDonorTiers($trends);
    }

    /**
     * Get top donors by total giving.
     *
     * @return Collection<int, Member>
     */
    public function getMajorDonors(Branch $branch, int $limit = 10, int $months = 12): Collection
    {
        return Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('donor_tier', 'top_10')
            ->orderByDesc('giving_consistency_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get donors with declining giving trends.
     *
     * @return Collection<int, Member>
     */
    public function getDecliningDonors(Branch $branch, float $threshold = -20, int $limit = 20): Collection
    {
        $config = config('ai.features.giving_trends', []);
        $declineThreshold = $config['decline_threshold_percent'] ?? $threshold;

        return Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'declining')
            ->where('giving_growth_rate', '<', $declineThreshold)
            ->orderBy('giving_growth_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * Get donors with growing giving trends.
     *
     * @return Collection<int, Member>
     */
    public function getGrowingDonors(Branch $branch, float $threshold = 20, int $limit = 20): Collection
    {
        $config = config('ai.features.giving_trends', []);
        $growthThreshold = $config['growth_threshold_percent'] ?? $threshold;

        return Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'growing')
            ->where('giving_growth_rate', '>=', $growthThreshold)
            ->orderByDesc('giving_growth_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * Get first-time donors (first donation within N days).
     *
     * @return Collection<int, Member>
     */
    public function getFirstTimeDonors(Branch $branch, int $days = 30): Collection
    {
        $cutoff = now()->subDays($days);

        // Find members whose first donation to this branch was within the period
        return Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'new')
            ->whereHas('donations', function ($query) use ($branch, $cutoff): void {
                $query->where('branch_id', $branch->id)
                    ->where('donation_date', '>=', $cutoff);
            })
            ->orderByDesc('giving_analyzed_at')
            ->limit(20)
            ->get();
    }

    /**
     * Get lapsed donors (no donation in N days).
     *
     * @return Collection<int, Member>
     */
    public function getLapsedDonors(Branch $branch, int $daysSinceLastDonation = 90): Collection
    {
        $config = config('ai.features.giving_trends', []);

        return Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->where('giving_trend', 'lapsed')
            ->limit(20)
            ->get();
    }

    /**
     * Get donor tier distribution for a branch.
     *
     * @return array<string, array{count: int, percentage: float, total_giving: float, share: float}>
     */
    public function getDonorTierDistribution(Branch $branch): array
    {
        $tiers = ['top_10', 'top_25', 'middle', 'bottom'];
        $distribution = [];

        $totalMembers = Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('donor_tier')
            ->count();

        // Get giving totals by tier
        $tierTotals = Donation::where('branch_id', $branch->id)
            ->whereNotNull('member_id')
            ->where('donation_date', '>=', now()->subMonths(12))
            ->join('members', 'donations.member_id', '=', 'members.id')
            ->whereNotNull('members.donor_tier')
            ->selectRaw('members.donor_tier, COUNT(DISTINCT members.id) as member_count, SUM(donations.amount) as total')
            ->groupBy('members.donor_tier')
            ->get()
            ->keyBy('donor_tier');

        $grandTotal = (float) $tierTotals->sum('total');

        foreach ($tiers as $tier) {
            $data = $tierTotals->get($tier);
            $count = $data?->member_count ?? 0;
            $total = (float) ($data?->total ?? 0);

            $distribution[$tier] = [
                'count' => $count,
                'percentage' => $totalMembers > 0 ? round(($count / $totalMembers) * 100, 1) : 0,
                'total_giving' => round($total, 2),
                'share' => $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0,
            ];
        }

        return $distribution;
    }

    /**
     * Get aggregate giving statistics for a branch.
     *
     * @return array<string, mixed>
     */
    public function getGivingStatistics(Branch $branch, int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();

        $donations = Donation::where('branch_id', $branch->id)
            ->where('donation_date', '>=', $startDate)
            ->get();

        $memberDonations = $donations->whereNotNull('member_id');
        $uniqueDonors = $memberDonations->pluck('member_id')->unique()->count();

        $totalMembers = Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->count();

        $trendCounts = Member::where('primary_branch_id', $branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereNotNull('giving_trend')
            ->selectRaw('giving_trend, COUNT(*) as count')
            ->groupBy('giving_trend')
            ->pluck('count', 'giving_trend')
            ->toArray();

        return [
            'total_donations' => round((float) $donations->sum('amount'), 2),
            'donation_count' => $donations->count(),
            'average_donation' => $donations->count() > 0 ? round((float) $donations->avg('amount'), 2) : 0,
            'unique_donors' => $uniqueDonors,
            'giving_percentage' => $totalMembers > 0 ? round(($uniqueDonors / $totalMembers) * 100, 1) : 0,
            'period_months' => $months,
            'trends' => [
                'growing' => $trendCounts['growing'] ?? 0,
                'stable' => $trendCounts['stable'] ?? 0,
                'declining' => $trendCounts['declining'] ?? 0,
                'new' => $trendCounts['new'] ?? 0,
                'lapsed' => $trendCounts['lapsed'] ?? 0,
            ],
        ];
    }

    /**
     * Update member model with giving trend data.
     */
    public function updateMemberGivingData(Member $member, GivingTrend $trend): bool
    {
        return $member->update([
            'giving_consistency_score' => (int) round($trend->consistencyScore),
            'giving_growth_rate' => $trend->growthRate,
            'donor_tier' => $trend->donorTier,
            'giving_trend' => $trend->trend,
            'giving_analyzed_at' => now(),
        ]);
    }

    /**
     * Process all members in a branch and update their giving data.
     */
    public function processBranch(Branch $branch, int $months = 12): int
    {
        $trends = $this->analyzeForBranch($branch, $months);
        $processed = 0;

        foreach ($trends as $trend) {
            $member = Member::find($trend->memberId);
            if ($member && $this->updateMemberGivingData($member, $trend)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Create an empty trend for members with no donations.
     */
    protected function createEmptyTrend(Member $member, int $months): GivingTrend
    {
        return new GivingTrend(
            memberId: $member->id,
            consistencyScore: 0,
            growthRate: 0,
            averageGift: 0,
            totalGiven: 0,
            donationCount: 0,
            donationsPerMonth: 0,
            donorTier: 'bottom',
            firstDonationDate: null,
            lastDonationDate: null,
            daysSinceLastDonation: 999,
            largestGift: 0,
            preferredType: null,
            preferredMethod: null,
            trend: 'lapsed',
            monthlyHistory: [],
            confidenceScore: 0,
        );
    }

    /**
     * Calculate monthly giving history.
     *
     * @return array<string, float>
     */
    protected function calculateMonthlyHistory(Collection $donations, int $months): array
    {
        $history = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $key = $monthStart->format('Y-m');

            $total = $donations
                ->filter(function ($donation) use ($monthStart, $monthEnd): bool {
                    $date = Carbon::parse($donation->donation_date);

                    return $date->gte($monthStart) && $date->lte($monthEnd);
                })
                ->sum('amount');

            $history[$key] = round((float) $total, 2);
        }

        return $history;
    }

    /**
     * Calculate growth rate comparing recent vs older period.
     */
    protected function calculateGrowthRate(array $monthlyHistory): float
    {
        $months = array_values($monthlyHistory);
        $count = count($months);

        if ($count < 6) {
            return 0; // Not enough data
        }

        // Split into recent 6 months vs previous period
        $recentMonths = array_slice($months, -6, 6);
        $olderMonths = array_slice($months, max(0, $count - 12), min(6, $count - 6));

        $recentTotal = array_sum($recentMonths);
        $olderTotal = array_sum($olderMonths);

        if ($olderTotal == 0) {
            return $recentTotal > 0 ? 100 : 0;
        }

        return (($recentTotal - $olderTotal) / $olderTotal) * 100;
    }

    /**
     * Calculate consistency score (0-100).
     */
    protected function calculateConsistencyScore(Collection $donations, array $monthlyHistory, int $months): float
    {
        if ($donations->isEmpty()) {
            return 0;
        }

        // Factor 1: Regularity (how many months had donations)
        $monthsWithDonations = count(array_filter($monthlyHistory, fn ($amount): bool => $amount > 0));
        $regularity = $monthsWithDonations / max(1, $months);

        // Factor 2: Gap penalty (large gaps reduce score)
        $gapPenalty = $this->calculateGapPenalty($donations);

        // Factor 3: Amount consistency (low variance is good)
        $amounts = array_filter($monthlyHistory, fn ($amount): bool => $amount > 0);
        $amountConsistency = 1;
        if (count($amounts) > 1) {
            $avg = array_sum($amounts) / count($amounts);
            if ($avg > 0) {
                $variance = array_sum(array_map(fn ($a): float|int => pow($a - $avg, 2), $amounts)) / count($amounts);
                $stdDev = sqrt($variance);
                $cv = $stdDev / $avg; // Coefficient of variation
                $amountConsistency = max(0, 1 - min(1, $cv));
            }
        }

        // Weighted combination
        $score = ($regularity * 0.5 + (1 - $gapPenalty) * 0.3 + $amountConsistency * 0.2) * 100;

        return max(0, min(100, $score));
    }

    /**
     * Calculate gap penalty based on donation frequency gaps.
     */
    protected function calculateGapPenalty(Collection $donations): float
    {
        if ($donations->count() < 2) {
            return 0;
        }

        $dates = $donations->pluck('donation_date')
            ->map(fn (\DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $d): \Carbon\Carbon => Carbon::parse($d))
            ->sortByDesc(fn ($d): \Carbon\Carbon => $d)
            ->values();

        $gaps = [];
        for ($i = 0; $i < min($dates->count() - 1, 11); $i++) {
            $gaps[] = $dates[$i]->diffInDays($dates[$i + 1]);
        }

        if ($gaps === []) {
            return 0;
        }

        $avgGap = array_sum($gaps) / count($gaps);
        $maxGap = max($gaps);

        // Penalize if max gap is much larger than average
        if ($avgGap > 0 && $maxGap > $avgGap * 3) {
            return min(1, ($maxGap - $avgGap) / 180);
        }

        // Penalize for long average gaps
        if ($avgGap > 60) {
            return min(1, ($avgGap - 30) / 150);
        }

        return 0;
    }

    /**
     * Get the most common donation type.
     */
    protected function getPreferredType(Collection $donations): ?DonationType
    {
        if ($donations->isEmpty()) {
            return null;
        }

        $typeCounts = $donations
            ->whereNotNull('donation_type')
            ->groupBy('donation_type')
            ->map(fn ($group): int => $group->count())
            ->sortDesc();

        $topType = $typeCounts->keys()->first();

        return $topType ? DonationType::tryFrom($topType) : null;
    }

    /**
     * Get the most common payment method.
     */
    protected function getPreferredMethod(Collection $donations): ?PaymentMethod
    {
        if ($donations->isEmpty()) {
            return null;
        }

        $methodCounts = $donations
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->map(fn ($group): int => $group->count())
            ->sortDesc();

        $topMethod = $methodCounts->keys()->first();

        return $topMethod ? PaymentMethod::tryFrom($topMethod) : null;
    }

    /**
     * Classify the giving trend.
     */
    protected function classifyTrend(int $daysSinceLastDonation, float $growthRate, ?Carbon $firstDonationDate): string
    {
        $config = config('ai.features.giving_trends', []);
        $lapsedThreshold = $config['lapsed_threshold_days'] ?? 90;
        $growthThreshold = $config['growth_threshold_percent'] ?? 20;
        $declineThreshold = $config['decline_threshold_percent'] ?? -20;

        // Check if new donor (first donation within 90 days)
        if ($firstDonationDate && $firstDonationDate->diffInDays(now()) < 90) {
            return 'new';
        }

        // Check if lapsed
        if ($daysSinceLastDonation > $lapsedThreshold) {
            return 'lapsed';
        }

        // Check growth/decline
        if ($growthRate >= $growthThreshold) {
            return 'growing';
        }

        if ($growthRate <= $declineThreshold) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Calculate confidence score based on data quality.
     */
    protected function calculateConfidence(int $donationCount, int $months): int
    {
        if ($donationCount === 0) {
            return 0;
        }

        // More donations = higher confidence
        $countFactor = min(1, $donationCount / 12);

        // Longer history = higher confidence
        $historyFactor = min(1, $months / 12);

        $confidence = (int) round(($countFactor * 0.7 + $historyFactor * 0.3) * 100);

        return max(0, min(100, $confidence));
    }

    /**
     * Assign donor tiers based on giving percentiles.
     *
     * @param  Collection<int, GivingTrend>  $trends
     * @return Collection<int, GivingTrend>
     */
    protected function assignDonorTiers(Collection $trends): Collection
    {
        if ($trends->isEmpty()) {
            return $trends;
        }

        // Sort by total given descending
        $sorted = $trends->sortByDesc(fn (GivingTrend $t): float => $t->totalGiven)->values();
        $count = $sorted->count();

        return $sorted->map(function (GivingTrend $trend, int $index) use ($count): \App\Services\AI\DTOs\GivingTrend {
            $percentile = (($count - $index) / $count) * 100;

            $tier = match (true) {
                $percentile > 90 => 'top_10',
                $percentile > 75 => 'top_25',
                $percentile > 50 => 'middle',
                default => 'bottom',
            };

            // Create new DTO with updated tier
            return new GivingTrend(
                memberId: $trend->memberId,
                consistencyScore: $trend->consistencyScore,
                growthRate: $trend->growthRate,
                averageGift: $trend->averageGift,
                totalGiven: $trend->totalGiven,
                donationCount: $trend->donationCount,
                donationsPerMonth: $trend->donationsPerMonth,
                donorTier: $tier,
                firstDonationDate: $trend->firstDonationDate,
                lastDonationDate: $trend->lastDonationDate,
                daysSinceLastDonation: $trend->daysSinceLastDonation,
                largestGift: $trend->largestGift,
                preferredType: $trend->preferredType,
                preferredMethod: $trend->preferredMethod,
                trend: $trend->trend,
                monthlyHistory: $trend->monthlyHistory,
                confidenceScore: $trend->confidenceScore,
            );
        });
    }
}
