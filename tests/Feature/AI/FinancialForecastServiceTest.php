<?php

declare(strict_types=1);

use App\Enums\DonationType;
use App\Jobs\AI\GenerateFinancialForecastJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\FinancialForecast;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\FinancialForecast as FinancialForecastDTO;
use App\Services\AI\FinancialForecastService;
use Carbon\Carbon;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $aiService = new AiService;
    $this->service = new FinancialForecastService($aiService);
    $this->branch = Branch::factory()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// DTO TESTS
// ============================================

it('creates FinancialForecast DTO from constructor', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 8500.00,
        confidenceUpper: 11500.00,
        confidence: 75.0,
        factors: ['historical_average' => ['value' => 9500]],
        cohortBreakdown: ['new' => ['member_count' => 10]],
        budgetTarget: 12000.00,
    );

    expect($dto->branchId)->toBe($this->branch->id);
    expect($dto->forecastType)->toBe('monthly');
    expect($dto->predictedTotal)->toBe(10000.00);
    expect($dto->confidence)->toBe(75.0);
});

it('calculates gap amount correctly', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 8500.00,
        confidenceUpper: 11500.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
        budgetTarget: 12000.00,
    );

    expect($dto->gapAmount())->toBe(-2000.00);
});

it('calculates gap percentage correctly', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 11000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1500.00,
        predictedOther: 1000.00,
        confidenceLower: 9500.00,
        confidenceUpper: 12500.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
        budgetTarget: 10000.00,
    );

    expect($dto->gapPercentage())->toBe(10.0);
});

it('determines on track status correctly', function (): void {
    $onTrack = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 12000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 3000.00,
        predictedSpecial: 2000.00,
        predictedOther: 1000.00,
        confidenceLower: 10000.00,
        confidenceUpper: 14000.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
        budgetTarget: 10000.00,
    );

    $atRisk = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 8000.00,
        predictedTithes: 5000.00,
        predictedOfferings: 2000.00,
        predictedSpecial: 500.00,
        predictedOther: 500.00,
        confidenceLower: 6500.00,
        confidenceUpper: 9500.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
        budgetTarget: 10000.00,
    );

    expect($onTrack->isOnTrack())->toBeTrue();
    expect($atRisk->isOnTrack())->toBeFalse();
});

it('returns null for on track when no budget target', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 8500.00,
        confidenceUpper: 11500.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
    );

    expect($dto->isOnTrack())->toBeNull();
    expect($dto->gapAmount())->toBeNull();
});

it('formats monthly period label correctly', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 8500.00,
        confidenceUpper: 11500.00,
        confidence: 75.0,
        factors: [],
        cohortBreakdown: [],
    );

    expect($dto->periodLabel())->toBe('March 2026');
});

it('formats quarterly period label correctly', function (): void {
    $dto = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'quarterly',
        periodStart: Carbon::parse('2026-04-01'),
        periodEnd: Carbon::parse('2026-06-30'),
        predictedTotal: 30000.00,
        predictedTithes: 18000.00,
        predictedOfferings: 7500.00,
        predictedSpecial: 3000.00,
        predictedOther: 1500.00,
        confidenceLower: 25000.00,
        confidenceUpper: 35000.00,
        confidence: 70.0,
        factors: [],
        cohortBreakdown: [],
    );

    expect($dto->periodLabel())->toBe('Q2 2026');
});

it('calculates confidence level correctly', function (): void {
    $high = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 9000.00,
        confidenceUpper: 11000.00,
        confidence: 85.0,
        factors: [],
        cohortBreakdown: [],
    );

    $medium = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 7500.00,
        confidenceUpper: 12500.00,
        confidence: 65.0,
        factors: [],
        cohortBreakdown: [],
    );

    $low = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 5000.00,
        confidenceUpper: 15000.00,
        confidence: 45.0,
        factors: [],
        cohortBreakdown: [],
    );

    expect($high->confidenceLevel())->toBe('high');
    expect($medium->confidenceLevel())->toBe('medium');
    expect($low->confidenceLevel())->toBe('low');
});

it('converts to array and back', function (): void {
    $original = new FinancialForecastDTO(
        branchId: $this->branch->id,
        forecastType: 'monthly',
        periodStart: Carbon::parse('2026-03-01'),
        periodEnd: Carbon::parse('2026-03-31'),
        predictedTotal: 10000.00,
        predictedTithes: 6000.00,
        predictedOfferings: 2500.00,
        predictedSpecial: 1000.00,
        predictedOther: 500.00,
        confidenceLower: 8500.00,
        confidenceUpper: 11500.00,
        confidence: 75.0,
        factors: ['seasonal' => ['value' => 5]],
        cohortBreakdown: ['new' => ['count' => 10]],
        budgetTarget: 12000.00,
    );

    $array = $original->toArray();
    $restored = FinancialForecastDTO::fromArray($array);

    expect($restored->branchId)->toBe($original->branchId);
    expect($restored->forecastType)->toBe($original->forecastType);
    expect($restored->predictedTotal)->toBe($original->predictedTotal);
    expect($restored->confidence)->toBe($original->confidence);
    expect($restored->budgetTarget)->toBe($original->budgetTarget);
});

// ============================================
// SERVICE TESTS
// ============================================

it('generates monthly forecast for branch with no donations', function (): void {
    $monthStart = Carbon::parse('2026-03-01');

    $result = $this->service->forecastMonthly($this->branch, $monthStart);

    expect($result)->toBeInstanceOf(FinancialForecastDTO::class);
    expect($result->forecastType)->toBe('monthly');
    expect($result->predictedTotal)->toBe(0.0);
});

it('generates monthly forecast with historical data', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create historical donations (6 months back)
    for ($month = 1; $month <= 6; $month++) {
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 1000.00,
            'donation_type' => DonationType::Tithe,
            'donation_date' => now()->subMonths($month)->startOfMonth()->addDays(5),
        ]);
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 500.00,
            'donation_type' => DonationType::Offering,
            'donation_date' => now()->subMonths($month)->startOfMonth()->addDays(10),
        ]);
    }

    $monthStart = now()->startOfMonth();

    $result = $this->service->forecastMonthly($this->branch, $monthStart);

    expect($result)->toBeInstanceOf(FinancialForecastDTO::class);
    expect($result->predictedTotal)->toBeGreaterThan(0);
    expect($result->confidence)->toBeGreaterThan(40);
});

it('generates quarterly forecast aggregating monthly data', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create historical donations
    for ($month = 1; $month <= 12; $month++) {
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 1500.00,
            'donation_type' => DonationType::Tithe,
            'donation_date' => now()->subMonths($month)->startOfMonth()->addDays(5),
        ]);
    }

    $quarterStart = now()->startOfMonth();

    $result = $this->service->forecastQuarterly($this->branch, $quarterStart);

    expect($result)->toBeInstanceOf(FinancialForecastDTO::class);
    expect($result->forecastType)->toBe('quarterly');
    // Quarterly should sum 3 months
    expect($result->predictedTotal)->toBeGreaterThan(0);
});

it('generates multiple forecasts for periods ahead', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create minimal historical data
    for ($month = 1; $month <= 6; $month++) {
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 1000.00,
            'donation_date' => now()->subMonths($month)->startOfMonth(),
        ]);
    }

    $forecasts = $this->service->generateForecasts($this->branch->id, 'monthly', 4);

    expect($forecasts)->toHaveCount(4);
    expect($forecasts->first()->forecastType)->toBe('monthly');
});

it('detects seasonal patterns', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create donations for each month of past 2 years with varying amounts
    for ($year = 0; $year < 2; $year++) {
        for ($month = 1; $month <= 12; $month++) {
            $amount = match ($month) {
                12 => 2500.00, // December higher
                7, 8 => 800.00, // Summer lower
                default => 1200.00,
            };

            Donation::factory()->for($this->branch)->for($member)->create([
                'amount' => $amount,
                'donation_date' => now()->subYears($year)->setMonth($month)->startOfMonth(),
            ]);
        }
    }

    $patterns = $this->service->detectSeasonalPatterns($this->branch->id);

    expect($patterns)->toHaveCount(12);
    expect($patterns[12]['trend'])->toBe('high');
    expect($patterns[7]['trend'])->toBe('low');
});

it('analyzes cohorts by join date', function (): void {
    // Create members in different cohorts
    $newMember = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'joined_at' => now()->subMonths(6),
    ]);
    $establishedMember = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'joined_at' => now()->subMonths(24),
    ]);
    $veteranMember = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'joined_at' => now()->subMonths(72),
    ]);

    // Create donations for each
    Donation::factory()->for($this->branch)->for($newMember)->create([
        'amount' => 500.00,
        'donation_date' => now()->subMonth(),
    ]);
    Donation::factory()->for($this->branch)->for($establishedMember)->create([
        'amount' => 1000.00,
        'donation_date' => now()->subMonth(),
    ]);
    Donation::factory()->for($this->branch)->for($veteranMember)->create([
        'amount' => 2000.00,
        'donation_date' => now()->subMonth(),
    ]);

    $cohorts = $this->service->analyzeCohortsByJoinDate($this->branch->id);

    expect($cohorts)->toHaveKey('new');
    expect($cohorts)->toHaveKey('established');
    expect($cohorts)->toHaveKey('veteran');
    expect($cohorts['veteran']['total_giving'])->toBe(2000.0);
});

it('updates forecasts in database', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    for ($month = 1; $month <= 6; $month++) {
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 1000.00,
            'donation_date' => now()->subMonths($month)->startOfMonth(),
        ]);
    }

    $updated = $this->service->updateForecastsForBranch($this->branch->id, 'monthly', 3);

    expect($updated)->toBe(3);
    expect(FinancialForecast::where('branch_id', $this->branch->id)->count())->toBe(3);
});

// ============================================
// MODEL TESTS
// ============================================

it('creates financial forecast model', function (): void {
    $forecast = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'predicted_total' => 10000.00,
        'predicted_tithes' => 6000.00,
        'predicted_offerings' => 2500.00,
        'predicted_special' => 1000.00,
        'predicted_other' => 500.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
        'factors' => ['test' => true],
        'cohort_breakdown' => ['new' => 10],
    ]);

    expect($forecast)->toBeInstanceOf(FinancialForecast::class);
    expect($forecast->branch_id)->toBe($this->branch->id);
    expect((float) $forecast->predicted_total)->toBe(10000.00);
    expect($forecast->factors)->toBe(['test' => true]);
});

it('casts dates and decimals correctly', function (): void {
    $forecast = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'predicted_total' => 10000.50,
        'predicted_tithes' => 6000.00,
        'predicted_offerings' => 2500.00,
        'predicted_special' => 1000.00,
        'predicted_other' => 500.50,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.5,
    ]);

    expect($forecast->period_start)->toBeInstanceOf(Carbon::class);
    expect($forecast->period_end)->toBeInstanceOf(Carbon::class);
});

it('scopes monthly forecasts', function (): void {
    FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'predicted_total' => 10000.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
    ]);

    FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'quarterly',
        'period_start' => '2026-04-01',
        'period_end' => '2026-06-30',
        'predicted_total' => 30000.00,
        'confidence_lower' => 25000.00,
        'confidence_upper' => 35000.00,
        'confidence_score' => 70.0,
    ]);

    $monthly = FinancialForecast::monthly()->get();
    $quarterly = FinancialForecast::quarterly()->get();

    expect($monthly)->toHaveCount(1);
    expect($quarterly)->toHaveCount(1);
});

it('calculates accuracy attribute', function (): void {
    $forecast = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'predicted_total' => 10000.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
        'actual_total' => 9500.00,
    ]);

    expect($forecast->accuracy)->toBe(95.0); // 5% off = 95% accuracy
});

it('calculates variance attribute', function (): void {
    $forecast = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'predicted_total' => 10000.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
        'actual_total' => 11000.00,
    ]);

    expect($forecast->variance)->toBe(1000.0);
    expect($forecast->variance_percent)->toBe(10.0);
});

it('generates period label attribute', function (): void {
    $monthly = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
        'predicted_total' => 10000.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
    ]);

    $quarterly = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'quarterly',
        'period_start' => '2026-04-01',
        'period_end' => '2026-06-30',
        'predicted_total' => 30000.00,
        'confidence_lower' => 25000.00,
        'confidence_upper' => 35000.00,
        'confidence_score' => 70.0,
    ]);

    expect($monthly->period_label)->toBe('March 2026');
    expect($quarterly->period_label)->toBe('Q2 2026');
});

it('records actual total correctly', function (): void {
    $forecast = FinancialForecast::create([
        'branch_id' => $this->branch->id,
        'forecast_type' => 'monthly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'predicted_total' => 10000.00,
        'confidence_lower' => 8500.00,
        'confidence_upper' => 11500.00,
        'confidence_score' => 75.0,
        'budget_target' => 12000.00,
    ]);

    $forecast->recordActual(11500.00);

    $forecast->refresh();

    expect((float) $forecast->actual_total)->toBe(11500.00);
    expect((float) $forecast->gap_amount)->toBe(-500.00); // 11500 - 12000
});

// ============================================
// JOB TESTS
// ============================================

it('dispatches job and creates forecast', function (): void {
    config(['ai.features.financial_forecast.enabled' => true]);

    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    for ($month = 1; $month <= 6; $month++) {
        Donation::factory()->for($this->branch)->for($member)->create([
            'amount' => 1000.00,
            'donation_date' => now()->subMonths($month)->startOfMonth(),
        ]);
    }

    $job = new GenerateFinancialForecastJob(
        $this->branch->id,
        'monthly',
        3
    );

    dispatch_sync($job);

    $forecasts = FinancialForecast::where('branch_id', $this->branch->id)->get();

    expect($forecasts)->toHaveCount(3);
});

it('skips job when feature is disabled', function (): void {
    config(['ai.features.financial_forecast.enabled' => false]);

    $job = new GenerateFinancialForecastJob(
        $this->branch->id,
        'monthly',
        3
    );

    dispatch_sync($job);

    expect(FinancialForecast::where('branch_id', $this->branch->id)->count())->toBe(0);
});
