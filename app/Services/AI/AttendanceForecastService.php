<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceForecast as AttendanceForecastModel;
use App\Models\Tenant\Service;
use App\Services\AI\DTOs\AttendanceForecast;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceForecastService
{
    /**
     * Weights for weighted moving average (most recent first).
     */
    protected array $weekWeights = [0.25, 0.20, 0.15, 0.12, 0.10, 0.08, 0.05, 0.03, 0.02];

    /**
     * Month-based seasonal factors.
     */
    protected array $monthFactors = [
        1 => 0.95,   // January - post-holiday dip
        2 => 1.00,
        3 => 1.00,
        4 => 1.05,   // Easter season
        5 => 1.00,
        6 => 0.95,   // Early summer
        7 => 0.85,   // Summer low
        8 => 0.85,   // Summer low
        9 => 1.00,   // Back to school
        10 => 1.00,
        11 => 1.00,
        12 => 1.10,  // Christmas season
    ];

    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Generate a forecast for a specific service on a specific date.
     */
    public function forecastForService(Service $service, Carbon $date): AttendanceForecast
    {
        $config = config('ai.scoring.forecast', []);
        $historyWeeks = config('ai.features.attendance_forecast.history_weeks', 12);

        // Get historical attendance for this service's day of week
        $historicalData = $this->getHistoricalAttendance($service, $historyWeeks);

        // Calculate base prediction using weighted moving average
        $basePrediction = $this->calculateWeightedAverage($historicalData['totals']);

        // Get member/visitor split from historical data
        $memberRatio = $this->calculateMemberRatio($historicalData);

        // Apply adjustments
        $seasonalFactor = $this->getSeasonalFactor($date);
        $trendFactor = $this->calculateTrendFactor($historicalData['totals']);

        // Final prediction
        $predictedTotal = (int) round($basePrediction * $seasonalFactor * $trendFactor);
        $predictedMembers = (int) round($predictedTotal * $memberRatio);
        $predictedVisitors = $predictedTotal - $predictedMembers;

        // Calculate confidence
        $confidence = $this->calculateConfidence($historicalData['totals'], $config);

        // Build factors explanation
        $factors = $this->buildFactors($seasonalFactor, $trendFactor, $historicalData, $date);

        // Calculate capacity percentage if capacity is set
        $capacityPercent = null;
        if ($service->capacity && $service->capacity > 0) {
            $capacityPercent = (int) round(($predictedTotal / $service->capacity) * 100);
        }

        return new AttendanceForecast(
            serviceId: $service->id,
            serviceName: $service->name,
            forecastDate: $date,
            predictedAttendance: max(0, $predictedTotal),
            predictedMembers: max(0, $predictedMembers),
            predictedVisitors: max(0, $predictedVisitors),
            confidence: $confidence,
            factors: $factors,
            capacityPercent: $capacityPercent,
        );
    }

    /**
     * Generate forecasts for all services in a branch for a date range.
     *
     * @return Collection<int, AttendanceForecast>
     */
    public function forecastForBranch(string $branchId, Carbon $startDate, Carbon $endDate): Collection
    {
        $services = Service::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $forecasts = collect();

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            foreach ($services as $service) {
                // Only forecast for dates that match this service's day of week
                if ($currentDate->dayOfWeek === $service->day_of_week) {
                    $forecasts->push($this->forecastForService($service, $currentDate->copy()));
                }
            }
            $currentDate->addDay();
        }

        return $forecasts->sortBy('forecastDate');
    }

    /**
     * Get next week's forecasts for a branch.
     *
     * @return Collection<int, AttendanceForecast>
     */
    public function getWeeklyForecast(string $branchId): Collection
    {
        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        return $this->forecastForBranch($branchId, $startDate, $endDate);
    }

    /**
     * Update forecasts in database for a branch.
     */
    public function updateForecastsForBranch(string $branchId, int $weeksAhead = 4): int
    {
        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->addWeeks($weeksAhead);

        $forecasts = $this->forecastForBranch($branchId, $startDate, $endDate);
        $updated = 0;

        foreach ($forecasts as $forecast) {
            AttendanceForecastModel::updateOrCreate(
                [
                    'service_id' => $forecast->serviceId,
                    'forecast_date' => $forecast->forecastDate->toDateString(),
                ],
                [
                    'branch_id' => $branchId,
                    'predicted_attendance' => $forecast->predictedAttendance,
                    'predicted_members' => $forecast->predictedMembers,
                    'predicted_visitors' => $forecast->predictedVisitors,
                    'confidence_score' => $forecast->confidence,
                    'factors' => $forecast->factors,
                ]
            );
            $updated++;
        }

        // Update service's quick-access forecast columns
        $this->updateServiceForecastColumns($branchId);

        return $updated;
    }

    /**
     * Update the quick-access forecast columns on services.
     */
    protected function updateServiceForecastColumns(string $branchId): void
    {
        $services = Service::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        foreach ($services as $service) {
            $nextForecast = AttendanceForecastModel::where('service_id', $service->id)
                ->where('forecast_date', '>=', today())
                ->orderBy('forecast_date')
                ->first();

            if ($nextForecast) {
                $service->update([
                    'forecast_next_attendance' => $nextForecast->predicted_attendance,
                    'forecast_confidence' => $nextForecast->confidence_score,
                    'forecast_factors' => $nextForecast->factors,
                    'forecast_calculated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Record actual attendance and update forecast accuracy.
     */
    public function recordActualAttendance(string $serviceId, Carbon $date, int $actualAttendance): bool
    {
        $forecast = AttendanceForecastModel::where('service_id', $serviceId)
            ->where('forecast_date', $date->toDateString())
            ->first();

        if ($forecast) {
            return $forecast->recordActual($actualAttendance);
        }

        return false;
    }

    /**
     * Calculate overall forecast accuracy for a branch.
     */
    public function calculateAccuracy(string $branchId, int $daysBack = 30): ?float
    {
        $forecasts = AttendanceForecastModel::where('branch_id', $branchId)
            ->whereNotNull('actual_attendance')
            ->where('forecast_date', '>=', now()->subDays($daysBack))
            ->get();

        if ($forecasts->isEmpty()) {
            return null;
        }

        $totalAccuracy = $forecasts->sum(fn ($f) => $f->accuracy ?? 0);

        return round($totalAccuracy / $forecasts->count(), 1);
    }

    /**
     * Get historical attendance data for a service.
     */
    protected function getHistoricalAttendance(Service $service, int $weeksBack): array
    {
        $totals = [];
        $members = [];
        $visitors = [];

        for ($week = 0; $week < $weeksBack; $week++) {
            $weekDate = now()->subWeeks($week + 1);

            // Find the specific date that matches this service's day of week
            while ($weekDate->dayOfWeek !== $service->day_of_week) {
                $weekDate->subDay();
            }

            $attendance = Attendance::where('service_id', $service->id)
                ->whereDate('date', $weekDate->toDateString())
                ->get();

            $totals[$week] = $attendance->count();
            $members[$week] = $attendance->whereNotNull('member_id')->count();
            $visitors[$week] = $attendance->whereNotNull('visitor_id')->count();
        }

        return [
            'totals' => $totals,
            'members' => $members,
            'visitors' => $visitors,
            'weeks_with_data' => count(array_filter($totals)),
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
            $weight = $this->weekWeights[$i] ?? 0.01;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Calculate the member-to-total ratio from historical data.
     */
    protected function calculateMemberRatio(array $historicalData): float
    {
        $totalMembers = array_sum($historicalData['members']);
        $totalAll = array_sum($historicalData['totals']);

        if ($totalAll === 0) {
            return 0.85; // Default assumption: 85% members
        }

        return $totalMembers / $totalAll;
    }

    /**
     * Get seasonal adjustment factor for a date.
     */
    protected function getSeasonalFactor(Carbon $date): float
    {
        $monthFactor = $this->monthFactors[$date->month] ?? 1.0;

        // Additional adjustments for specific weeks
        // Christmas week
        if ($date->month === 12 && $date->day >= 24 && $date->day <= 26) {
            $monthFactor *= 1.3; // Higher for Christmas Eve/Day
        }

        // New Year's week
        if (($date->month === 12 && $date->day >= 31) || ($date->month === 1 && $date->day <= 2)) {
            $monthFactor *= 0.7; // Lower for New Year
        }

        return $monthFactor;
    }

    /**
     * Calculate trend factor (growth or decline).
     */
    protected function calculateTrendFactor(array $totals): float
    {
        if (count($totals) < 4) {
            return 1.0; // Not enough data
        }

        // Compare first half to second half of historical data
        $halfPoint = (int) floor(count($totals) / 2);
        $recentHalf = array_slice($totals, 0, $halfPoint);
        $olderHalf = array_slice($totals, $halfPoint);

        $recentAvg = count($recentHalf) > 0 ? array_sum($recentHalf) / count($recentHalf) : 0;
        $olderAvg = count($olderHalf) > 0 ? array_sum($olderHalf) / count($olderHalf) : 0;

        if ($olderAvg === 0) {
            return 1.0;
        }

        $trend = $recentAvg / $olderAvg;

        // Limit trend factor to reasonable bounds (0.8 to 1.2)
        return max(0.8, min(1.2, $trend));
    }

    /**
     * Calculate prediction confidence score.
     */
    protected function calculateConfidence(array $totals, array $config): float
    {
        $baseConfidence = $config['base_confidence'] ?? 70;
        $minDataWeeks = $config['min_data_weeks'] ?? 4;

        // Filter out zero values
        $nonZeroValues = array_filter($totals, fn ($v): bool => $v > 0);
        $dataPoints = count($nonZeroValues);

        // Not enough data
        if ($dataPoints < $minDataWeeks) {
            return max(40, $baseConfidence - (($minDataWeeks - $dataPoints) * 10));
        }

        // Calculate coefficient of variation
        $mean = count($nonZeroValues) > 0 ? array_sum($nonZeroValues) / count($nonZeroValues) : 0;

        if ($mean === 0) {
            return 40;
        }

        $stdDev = $this->calculateStdDev($nonZeroValues, $mean);
        $coefficientOfVariation = $stdDev / $mean;

        // Lower variation = higher confidence
        $variationPenalty = $coefficientOfVariation * 50;
        $confidence = $baseConfidence - $variationPenalty;

        // Bonus for more data points
        $dataBonus = min(15, ($dataPoints - $minDataWeeks) * 3);

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
        $avgAttendance = count($historicalData['totals']) > 0
            ? round(array_sum($historicalData['totals']) / count($historicalData['totals']), 1)
            : 0;

        $factors['historical_average'] = [
            'description' => sprintf('Based on historical average of %.0f', $avgAttendance),
            'value' => $avgAttendance,
        ];

        // Seasonal factor
        if ($seasonalFactor !== 1.0) {
            $adjustment = ($seasonalFactor - 1) * 100;
            $factors['seasonal'] = [
                'description' => sprintf(
                    '%s adjustment for %s',
                    $adjustment > 0 ? 'Increased' : 'Decreased',
                    $date->format('F')
                ),
                'value' => round($adjustment, 0),
            ];
        }

        // Trend factor
        if (abs($trendFactor - 1.0) > 0.05) {
            $trendPercent = ($trendFactor - 1) * 100;
            $factors['trend'] = [
                'description' => sprintf(
                    '%s trend detected (%.0f%%)',
                    $trendFactor > 1 ? 'Growing' : 'Declining',
                    abs($trendPercent)
                ),
                'value' => round($trendPercent, 0),
            ];
        }

        // Data quality
        $weeksWithData = $historicalData['weeks_with_data'];
        if ($weeksWithData < 8) {
            $factors['data_quality'] = [
                'description' => sprintf('Limited data: %d weeks available', $weeksWithData),
                'value' => $weeksWithData,
            ];
        }

        return $factors;
    }

    /**
     * Check if the forecast feature is enabled.
     */
    public function isEnabled(): bool
    {
        return config('ai.features.attendance_forecast.enabled', false);
    }
}
