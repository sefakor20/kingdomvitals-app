<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\DonationType;
use App\Enums\PaymentTransactionStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\FinancialForecast as FinancialForecastModel;
use App\Models\Tenant\Member;
use App\Models\Tenant\PaymentTransaction;
use App\Services\AI\DTOs\FinancialForecast;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialForecastService
{
    /**
     * Weights for weighted moving average (most recent first).
     */
    protected array $monthWeights = [0.25, 0.20, 0.18, 0.12, 0.08, 0.06, 0.05, 0.03, 0.02, 0.01];

    /**
     * Month-based seasonal factors for church giving patterns.
     */
    protected array $monthFactors = [
        1 => 0.90,   // January - post-holiday dip
        2 => 0.95,
        3 => 0.95,
        4 => 1.00,   // Easter season typically brings increased giving
        5 => 0.98,
        6 => 0.92,   // Early summer
        7 => 0.85,   // Summer low
        8 => 0.85,   // Summer low
        9 => 0.95,   // Back-to-school, giving picks up
        10 => 1.00,
        11 => 1.05,  // Thanksgiving gratitude giving
        12 => 1.25,  // Year-end surge (tax-deductible giving)
    ];

    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Generate a monthly forecast for a branch.
     */
    public function forecastMonthly(Branch $branch, Carbon $monthStart): FinancialForecast
    {
        $config = config('ai.scoring.financial', []);
        $historyMonths = config('ai.features.financial_forecast.history_months', 24);

        // Get historical giving data
        $historicalData = $this->getHistoricalGiving($branch->id, $historyMonths);

        // Calculate base prediction using weighted moving average
        $basePrediction = $this->calculateWeightedAverage($historicalData['totals']);

        // Get giving type breakdown from historical data
        $typeBreakdown = $this->calculateTypeBreakdown($historicalData);

        // Apply adjustments
        $seasonalFactor = $this->getSeasonalFactor($monthStart);
        $trendFactor = $this->calculateTrendFactor($historicalData['totals']);

        // Final predictions
        $predictedTotal = max(0, $basePrediction * $seasonalFactor * $trendFactor);
        $predictedTithes = $predictedTotal * $typeBreakdown['tithes'];
        $predictedOfferings = $predictedTotal * $typeBreakdown['offerings'];
        $predictedSpecial = $predictedTotal * $typeBreakdown['special'];
        $predictedOther = $predictedTotal * $typeBreakdown['other'];

        // Calculate confidence interval
        $confidenceInterval = $this->calculateConfidenceInterval(
            $historicalData['totals'],
            $predictedTotal
        );

        // Calculate confidence score
        $confidence = $this->calculateConfidence($historicalData['totals'], $config);

        // Build factors explanation
        $factors = $this->buildFactors($seasonalFactor, $trendFactor, $historicalData, $monthStart);

        // Analyze cohorts by member tenure
        $cohortBreakdown = $this->analyzeCohortsByJoinDate($branch->id);

        return new FinancialForecast(
            branchId: $branch->id,
            forecastType: 'monthly',
            periodStart: $monthStart->copy()->startOfMonth(),
            periodEnd: $monthStart->copy()->endOfMonth(),
            predictedTotal: round($predictedTotal, 2),
            predictedTithes: round($predictedTithes, 2),
            predictedOfferings: round($predictedOfferings, 2),
            predictedSpecial: round($predictedSpecial, 2),
            predictedOther: round($predictedOther, 2),
            confidenceLower: round($confidenceInterval['lower'], 2),
            confidenceUpper: round($confidenceInterval['upper'], 2),
            confidence: $confidence,
            factors: $factors,
            cohortBreakdown: $cohortBreakdown, // Can be set from external source
        );
    }

    /**
     * Generate a quarterly forecast for a branch.
     */
    public function forecastQuarterly(Branch $branch, Carbon $quarterStart): FinancialForecast
    {
        // Generate monthly forecasts for the quarter
        $month1 = $this->forecastMonthly($branch, $quarterStart->copy());
        $month2 = $this->forecastMonthly($branch, $quarterStart->copy()->addMonth());
        $month3 = $this->forecastMonthly($branch, $quarterStart->copy()->addMonths(2));

        $quarterEnd = $quarterStart->copy()->addMonths(2)->endOfMonth();

        // Aggregate predictions
        $predictedTotal = $month1->predictedTotal + $month2->predictedTotal + $month3->predictedTotal;
        $predictedTithes = $month1->predictedTithes + $month2->predictedTithes + $month3->predictedTithes;
        $predictedOfferings = $month1->predictedOfferings + $month2->predictedOfferings + $month3->predictedOfferings;
        $predictedSpecial = $month1->predictedSpecial + $month2->predictedSpecial + $month3->predictedSpecial;
        $predictedOther = $month1->predictedOther + $month2->predictedOther + $month3->predictedOther;

        // Aggregate confidence intervals
        $confidenceLower = $month1->confidenceLower + $month2->confidenceLower + $month3->confidenceLower;
        $confidenceUpper = $month1->confidenceUpper + $month2->confidenceUpper + $month3->confidenceUpper;

        // Average confidence (weighted by predicted amounts)
        $totalPredicted = $month1->predictedTotal + $month2->predictedTotal + $month3->predictedTotal;
        $confidence = $totalPredicted > 0
            ? ($month1->confidence * $month1->predictedTotal +
               $month2->confidence * $month2->predictedTotal +
               $month3->confidence * $month3->predictedTotal) / $totalPredicted
            : ($month1->confidence + $month2->confidence + $month3->confidence) / 3;

        // Combine factors
        $factors = [
            'quarterly_composition' => [
                'description' => sprintf(
                    'Q%d %d: %s - %s',
                    (int) ceil($quarterStart->month / 3),
                    $quarterStart->year,
                    $quarterStart->format('M'),
                    $quarterEnd->format('M')
                ),
                'value' => 3,
            ],
            'monthly_breakdown' => [
                'description' => sprintf(
                    '%s: %s, %s: %s, %s: %s',
                    $month1->periodStart->format('M'),
                    number_format($month1->predictedTotal),
                    $month2->periodStart->format('M'),
                    number_format($month2->predictedTotal),
                    $month3->periodStart->format('M'),
                    number_format($month3->predictedTotal)
                ),
                'value' => $predictedTotal,
            ],
        ];

        return new FinancialForecast(
            branchId: $branch->id,
            forecastType: 'quarterly',
            periodStart: $quarterStart->copy()->startOfMonth(),
            periodEnd: $quarterEnd,
            predictedTotal: round($predictedTotal, 2),
            predictedTithes: round($predictedTithes, 2),
            predictedOfferings: round($predictedOfferings, 2),
            predictedSpecial: round($predictedSpecial, 2),
            predictedOther: round($predictedOther, 2),
            confidenceLower: round($confidenceLower, 2),
            confidenceUpper: round($confidenceUpper, 2),
            confidence: round($confidence, 1),
            factors: $factors,
            cohortBreakdown: $month1->cohortBreakdown,
        );
    }

    /**
     * Generate multiple forecasts for a branch.
     *
     * @return Collection<int, FinancialForecast>
     */
    public function generateForecasts(string $branchId, string $type = 'monthly', int $periodsAhead = 4): Collection
    {
        $branch = Branch::findOrFail($branchId);
        $forecasts = collect();

        for ($i = 0; $i < $periodsAhead; $i++) {
            if ($type === 'monthly') {
                $periodStart = now()->addMonths($i)->startOfMonth();
                $forecasts->push($this->forecastMonthly($branch, $periodStart));
            } elseif ($type === 'quarterly') {
                // Start from the current quarter
                $currentQuarter = (int) ceil(now()->month / 3);
                $quarterStart = now()->startOfYear()->addMonths(($currentQuarter - 1 + $i) * 3);
                $forecasts->push($this->forecastQuarterly($branch, $quarterStart));
            }
        }

        return $forecasts;
    }

    /**
     * Update forecasts in database for a branch.
     */
    public function updateForecastsForBranch(string $branchId, string $type = 'monthly', int $periodsAhead = 4): int
    {
        $forecasts = $this->generateForecasts($branchId, $type, $periodsAhead);
        $updated = 0;

        foreach ($forecasts as $forecast) {
            FinancialForecastModel::updateOrCreate(
                [
                    'branch_id' => $forecast->branchId,
                    'forecast_type' => $forecast->forecastType,
                    'period_start' => $forecast->periodStart->toDateString(),
                ],
                $forecast->toArray()
            );
            $updated++;
        }

        return $updated;
    }

    /**
     * Analyze giving patterns by member tenure cohorts.
     */
    public function analyzeCohortsByJoinDate(string $branchId): array
    {
        $cohorts = [
            'new' => ['label' => 'New (< 1 year)', 'min_months' => 0, 'max_months' => 12],
            'established' => ['label' => 'Established (1-3 years)', 'min_months' => 12, 'max_months' => 36],
            'mature' => ['label' => 'Mature (3-5 years)', 'min_months' => 36, 'max_months' => 60],
            'veteran' => ['label' => 'Veteran (5+ years)', 'min_months' => 60, 'max_months' => null],
        ];

        $result = [];
        $totalGiving = 0;

        foreach ($cohorts as $key => $cohort) {
            $query = Member::where('primary_branch_id', $branchId)
                ->where('joined_at', '<=', now()->subMonths($cohort['min_months']));

            if ($cohort['max_months'] !== null) {
                $query->where('joined_at', '>', now()->subMonths($cohort['max_months']));
            }

            $memberIds = $query->pluck('id');

            $giving = Donation::where('branch_id', $branchId)
                ->whereIn('member_id', $memberIds)
                ->where('donation_date', '>=', now()->subMonths(12))
                ->sum('amount');

            $memberCount = $memberIds->count();

            $result[$key] = [
                'label' => $cohort['label'],
                'member_count' => $memberCount,
                'total_giving' => (float) $giving,
                'avg_per_member' => $memberCount > 0 ? round($giving / $memberCount, 2) : 0,
            ];

            $totalGiving += $giving;
        }

        // Calculate percentages
        foreach ($result as $key => $cohort) {
            $result[$key]['percentage'] = $totalGiving > 0
                ? round(($cohort['total_giving'] / $totalGiving) * 100, 1)
                : 0;
        }

        return $result;
    }

    /**
     * Detect seasonal income patterns for a branch (donations + event revenue).
     */
    public function detectSeasonalPatterns(string $branchId): array
    {
        $patterns = [];

        // Get monthly donation totals for the past 2 years
        $monthlyDonations = Donation::where('branch_id', $branchId)
            ->where('donation_date', '>=', now()->subYears(2))
            ->selectRaw('YEAR(donation_date) as year, MONTH(donation_date) as month, SUM(amount) as total')
            ->groupByRaw('YEAR(donation_date), MONTH(donation_date)')
            ->orderByRaw('YEAR(donation_date), MONTH(donation_date)')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.$item->month);

        // Get monthly event revenue totals for the past 2 years
        $monthlyEventRevenue = PaymentTransaction::query()
            ->where('branch_id', $branchId)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->where('paid_at', '>=', now()->subYears(2))
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupByRaw('YEAR(paid_at), MONTH(paid_at)')
            ->orderByRaw('YEAR(paid_at), MONTH(paid_at)')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.$item->month);

        // Calculate combined average by month
        $monthAverages = [];
        $twoYearsAgo = now()->subYears(2);

        for ($i = 0; $i < 24; $i++) {
            $date = $twoYearsAgo->copy()->addMonths($i);
            $key = $date->year.'-'.$date->month;
            $month = $date->month;

            $donationTotal = $monthlyDonations->get($key)?->total ?? 0;
            $eventTotal = $monthlyEventRevenue->get($key)?->total ?? 0;
            $combinedTotal = (float) $donationTotal + (float) $eventTotal;

            if (! isset($monthAverages[$month])) {
                $monthAverages[$month] = ['total' => 0, 'count' => 0];
            }
            $monthAverages[$month]['total'] += $combinedTotal;
            $monthAverages[$month]['count']++;
        }

        $overallAverage = 0;
        $monthCount = 0;
        foreach ($monthAverages as $month => $data) {
            $monthAverages[$month]['average'] = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
            $overallAverage += $monthAverages[$month]['average'];
            $monthCount++;
        }
        $overallAverage = $monthCount > 0 ? $overallAverage / $monthCount : 0;

        // Calculate seasonal index for each month
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        foreach ($monthNames as $monthNum => $monthName) {
            $avg = $monthAverages[$monthNum]['average'] ?? 0;
            $index = $overallAverage > 0 ? round(($avg / $overallAverage) * 100, 1) : 100;

            $patterns[$monthNum] = [
                'month' => $monthName,
                'average' => round($avg, 2),
                'seasonal_index' => $index,
                'trend' => $index >= 105 ? 'high' : ($index <= 95 ? 'low' : 'normal'),
            ];
        }

        return $patterns;
    }

    /**
     * Analyze gap between forecast and budget target.
     */
    public function analyzeGap(string $branchId, string $periodType = 'monthly'): array
    {
        $forecasts = FinancialForecastModel::where('branch_id', $branchId)
            ->where('forecast_type', $periodType)
            ->whereNotNull('budget_target')
            ->upcoming()
            ->limit(6)
            ->get();

        $analysis = [
            'periods' => [],
            'total_predicted' => 0,
            'total_budget' => 0,
            'total_gap' => 0,
            'at_risk_periods' => 0,
        ];

        foreach ($forecasts as $forecast) {
            $gap = $forecast->predicted_total - $forecast->budget_target;
            $gapPercent = $forecast->budget_target > 0
                ? round(($gap / $forecast->budget_target) * 100, 1)
                : 0;

            $analysis['periods'][] = [
                'period' => $forecast->period_label,
                'predicted' => (float) $forecast->predicted_total,
                'budget' => (float) $forecast->budget_target,
                'gap' => round($gap, 2),
                'gap_percent' => $gapPercent,
                'is_at_risk' => $gap < 0,
            ];

            $analysis['total_predicted'] += $forecast->predicted_total;
            $analysis['total_budget'] += $forecast->budget_target;
            $analysis['total_gap'] += $gap;

            if ($gap < 0) {
                $analysis['at_risk_periods']++;
            }
        }

        $analysis['overall_gap_percent'] = $analysis['total_budget'] > 0
            ? round(($analysis['total_gap'] / $analysis['total_budget']) * 100, 1)
            : 0;

        return $analysis;
    }

    /**
     * Record actual total for a forecast period.
     */
    public function recordActual(string $branchId, string $type, Carbon $periodStart, float $actualTotal): bool
    {
        $forecast = FinancialForecastModel::where('branch_id', $branchId)
            ->where('forecast_type', $type)
            ->whereDate('period_start', $periodStart->toDateString())
            ->first();

        if ($forecast) {
            return $forecast->recordActual($actualTotal);
        }

        return false;
    }

    /**
     * Calculate overall forecast accuracy for a branch.
     */
    public function calculateAccuracy(string $branchId, int $monthsBack = 6): ?float
    {
        $forecasts = FinancialForecastModel::where('branch_id', $branchId)
            ->whereNotNull('actual_total')
            ->where('period_start', '>=', now()->subMonths($monthsBack))
            ->get();

        if ($forecasts->isEmpty()) {
            return null;
        }

        $totalAccuracy = $forecasts->sum(fn ($f) => $f->accuracy ?? 0);

        return round($totalAccuracy / $forecasts->count(), 1);
    }

    /**
     * Get historical giving data for a branch (donations + event revenue).
     */
    protected function getHistoricalGiving(string $branchId, int $monthsBack): array
    {
        $totals = [];
        $tithes = [];
        $offerings = [];
        $special = [];
        $other = [];
        $eventRevenue = [];

        for ($month = 0; $month < $monthsBack; $month++) {
            $monthStart = now()->subMonths($month + 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            // Get donation data
            $donations = Donation::where('branch_id', $branchId)
                ->whereBetween('donation_date', [$monthStart, $monthEnd])
                ->get();

            $donationTotal = (float) $donations->sum('amount');
            $tithes[$month] = (float) $donations->where('donation_type', DonationType::Tithe)->sum('amount');
            $offerings[$month] = (float) $donations->where('donation_type', DonationType::Offering)->sum('amount');
            $special[$month] = (float) $donations
                ->whereIn('donation_type', [DonationType::Special, DonationType::BuildingFund, DonationType::Missions])
                ->sum('amount');
            $other[$month] = (float) $donations
                ->whereIn('donation_type', [DonationType::Welfare, DonationType::Other])
                ->sum('amount');

            // Get event revenue
            $eventRevenueAmount = (float) PaymentTransaction::query()
                ->where('branch_id', $branchId)
                ->whereNotNull('event_registration_id')
                ->where('status', PaymentTransactionStatus::Success)
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $eventRevenue[$month] = $eventRevenueAmount;

            // Total income = donations + event revenue
            $totals[$month] = $donationTotal + $eventRevenueAmount;
        }

        return [
            'totals' => $totals,
            'tithes' => $tithes,
            'offerings' => $offerings,
            'special' => $special,
            'other' => $other,
            'event_revenue' => $eventRevenue,
            'months_with_data' => count(array_filter($totals)),
        ];
    }

    /**
     * Calculate weighted moving average.
     */
    protected function calculateWeightedAverage(array $values): float
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($values as $i => $value) {
            $weight = $this->monthWeights[$i] ?? 0.01;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Calculate the giving type breakdown from historical data.
     */
    protected function calculateTypeBreakdown(array $historicalData): array
    {
        $totalTithes = array_sum($historicalData['tithes']);
        $totalOfferings = array_sum($historicalData['offerings']);
        $totalSpecial = array_sum($historicalData['special']);
        $totalOther = array_sum($historicalData['other']);
        $total = array_sum($historicalData['totals']);

        if ($total == 0) {
            // Default breakdown based on typical church patterns
            return [
                'tithes' => 0.60,
                'offerings' => 0.25,
                'special' => 0.10,
                'other' => 0.05,
            ];
        }

        return [
            'tithes' => $totalTithes / $total,
            'offerings' => $totalOfferings / $total,
            'special' => $totalSpecial / $total,
            'other' => $totalOther / $total,
        ];
    }

    /**
     * Get seasonal adjustment factor for a month.
     */
    protected function getSeasonalFactor(Carbon $date): float
    {
        $monthFactor = $this->monthFactors[$date->month] ?? 1.0;

        // Additional adjustments for specific periods

        // Christmas week boost
        if ($date->month === 12 && $date->day >= 20) {
            $monthFactor *= 1.15;
        }

        // End of tax year (December 31 push)
        if ($date->month === 12 && $date->day >= 28) {
            $monthFactor *= 1.10;
        }

        return $monthFactor;
    }

    /**
     * Calculate trend factor (growth or decline).
     */
    protected function calculateTrendFactor(array $totals): float
    {
        if (count($totals) < 6) {
            return 1.0; // Not enough data
        }

        // Compare first half to second half of historical data
        $halfPoint = (int) floor(count($totals) / 2);
        $recentHalf = array_slice($totals, 0, $halfPoint);
        $olderHalf = array_slice($totals, $halfPoint);

        $recentAvg = count($recentHalf) > 0 ? array_sum($recentHalf) / count($recentHalf) : 0;
        $olderAvg = count($olderHalf) > 0 ? array_sum($olderHalf) / count($olderHalf) : 0;

        if ($olderAvg == 0) {
            return 1.0;
        }

        $trend = $recentAvg / $olderAvg;

        // Limit trend factor to reasonable bounds (0.85 to 1.15)
        return max(0.85, min(1.15, $trend));
    }

    /**
     * Calculate confidence interval for prediction.
     */
    protected function calculateConfidenceInterval(array $totals, float $prediction): array
    {
        $nonZeroValues = array_filter($totals, fn ($v): bool => $v > 0);

        if (count($nonZeroValues) < 3) {
            // Wide interval for limited data
            return [
                'lower' => $prediction * 0.70,
                'upper' => $prediction * 1.30,
            ];
        }

        $mean = array_sum($nonZeroValues) / count($nonZeroValues);
        $stdDev = $this->calculateStdDev($nonZeroValues, $mean);

        // 95% confidence interval (approximately 1.96 standard deviations)
        $margin = 1.96 * ($stdDev / sqrt(count($nonZeroValues)));

        return [
            'lower' => max(0, $prediction - $margin),
            'upper' => $prediction + $margin,
        ];
    }

    /**
     * Calculate prediction confidence score.
     */
    protected function calculateConfidence(array $totals, array $config): float
    {
        $baseConfidence = $config['base_confidence'] ?? 70;
        $minDataMonths = $config['min_data_months'] ?? 6;

        // Filter out zero values
        $nonZeroValues = array_filter($totals, fn ($v): bool => $v > 0);
        $dataPoints = count($nonZeroValues);

        // Not enough data
        if ($dataPoints < $minDataMonths) {
            return max(40, $baseConfidence - (($minDataMonths - $dataPoints) * 8));
        }

        // Calculate coefficient of variation
        $mean = count($nonZeroValues) > 0 ? array_sum($nonZeroValues) / count($nonZeroValues) : 0;

        if ($mean === 0) {
            return 40;
        }

        $stdDev = $this->calculateStdDev($nonZeroValues, $mean);
        $coefficientOfVariation = $stdDev / $mean;

        // Lower variation = higher confidence
        $variationPenalty = $coefficientOfVariation * 40;
        $confidence = $baseConfidence - $variationPenalty;

        // Bonus for more data points
        $dataBonus = min(12, ($dataPoints - $minDataMonths) * 2);

        return max(40, min(95, round($confidence + $dataBonus, 1)));
    }

    /**
     * Calculate standard deviation.
     */
    protected function calculateStdDev(array $values, float $mean): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $variance = array_reduce($values, function (int|float $carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / count($values);

        return sqrt($variance);
    }

    /**
     * Build factors array for explanation.
     */
    protected function buildFactors(
        float $seasonalFactor,
        float $trendFactor,
        array $historicalData,
        Carbon $date
    ): array {
        $factors = [];

        // Historical average factor
        $avgGiving = count($historicalData['totals']) > 0
            ? round(array_sum($historicalData['totals']) / count($historicalData['totals']), 2)
            : 0;

        $factors['historical_average'] = [
            'description' => sprintf('Based on historical monthly average of %s', number_format($avgGiving, 2)),
            'value' => $avgGiving,
        ];

        // Seasonal factor
        if ($seasonalFactor !== 1.0) {
            $adjustment = ($seasonalFactor - 1) * 100;
            $factors['seasonal'] = [
                'description' => sprintf(
                    '%s adjustment (%.0f%%) for %s giving patterns',
                    $adjustment > 0 ? 'Increased' : 'Decreased',
                    abs($adjustment),
                    $date->format('F')
                ),
                'value' => round($adjustment, 0),
            ];
        }

        // Trend factor
        if (abs($trendFactor - 1.0) > 0.03) {
            $trendPercent = ($trendFactor - 1) * 100;
            $factors['trend'] = [
                'description' => sprintf(
                    '%s giving trend detected (%.0f%%)',
                    $trendFactor > 1 ? 'Growing' : 'Declining',
                    abs($trendPercent)
                ),
                'value' => round($trendPercent, 0),
            ];
        }

        // Data quality
        $monthsWithData = $historicalData['months_with_data'];
        if ($monthsWithData < 12) {
            $factors['data_quality'] = [
                'description' => sprintf('Limited data: %d months available', $monthsWithData),
                'value' => $monthsWithData,
            ];
        }

        return $factors;
    }

    /**
     * Check if the forecast feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.financial_forecast.enabled', false);
    }
}
