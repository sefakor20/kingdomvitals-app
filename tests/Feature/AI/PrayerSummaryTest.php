<?php

declare(strict_types=1);

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestStatus;
use App\Enums\PrayerUrgencyLevel;
use App\Jobs\AI\GeneratePrayerSummaryJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\PrayerSummary;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\PrayerSummaryData;
use App\Services\AI\PrayerAnalysisService;
use Carbon\Carbon;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $aiService = new AiService;
    $this->service = new PrayerAnalysisService($aiService);
    $this->branch = Branch::factory()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// DTO TESTS
// ============================================

it('creates PrayerSummaryData from constructor', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'weekly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-07'),
        categoryBreakdown: ['health' => 5, 'family' => 3],
        urgencyBreakdown: ['normal' => 6, 'elevated' => 2],
        summaryText: 'This week saw prayer requests focused on health.',
        keyThemes: ['Health concerns', 'Family matters'],
        pastoralRecommendations: ['Consider a healing service'],
        totalRequests: 8,
        answeredRequests: 2,
        criticalRequests: 0,
    );

    expect($data->periodType)->toBe('weekly');
    expect($data->totalRequests)->toBe(8);
    expect($data->answeredRequests)->toBe(2);
    expect($data->categoryBreakdown)->toBe(['health' => 5, 'family' => 3]);
});

it('calculates answer rate correctly', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'weekly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-07'),
        categoryBreakdown: [],
        urgencyBreakdown: [],
        summaryText: '',
        keyThemes: [],
        pastoralRecommendations: [],
        totalRequests: 10,
        answeredRequests: 4,
        criticalRequests: 0,
    );

    expect($data->answerRate())->toBe(40.0);
});

it('handles zero total requests for answer rate', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'weekly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-07'),
        categoryBreakdown: [],
        urgencyBreakdown: [],
        summaryText: '',
        keyThemes: [],
        pastoralRecommendations: [],
        totalRequests: 0,
        answeredRequests: 0,
        criticalRequests: 0,
    );

    expect($data->answerRate())->toBe(0.0);
});

it('identifies top category', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'weekly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-07'),
        categoryBreakdown: ['health' => 5, 'family' => 10, 'finances' => 3],
        urgencyBreakdown: [],
        summaryText: '',
        keyThemes: [],
        pastoralRecommendations: [],
        totalRequests: 18,
        answeredRequests: 0,
        criticalRequests: 0,
    );

    expect($data->topCategory())->toBe('family');
});

it('converts to array and back', function (): void {
    $original = new PrayerSummaryData(
        periodType: 'monthly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-28'),
        categoryBreakdown: ['health' => 5],
        urgencyBreakdown: ['normal' => 5],
        summaryText: 'Summary text',
        keyThemes: ['Theme 1'],
        pastoralRecommendations: ['Recommendation 1'],
        totalRequests: 5,
        answeredRequests: 2,
        criticalRequests: 1,
    );

    $array = $original->toArray();
    $restored = PrayerSummaryData::fromArray($array);

    expect($restored->periodType)->toBe($original->periodType);
    expect($restored->totalRequests)->toBe($original->totalRequests);
    expect($restored->summaryText)->toBe($original->summaryText);
});

it('formats weekly period label correctly', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'weekly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-07'),
        categoryBreakdown: [],
        urgencyBreakdown: [],
        summaryText: '',
        keyThemes: [],
        pastoralRecommendations: [],
        totalRequests: 0,
        answeredRequests: 0,
        criticalRequests: 0,
    );

    expect($data->periodLabel())->toBe('Feb 1 - Feb 7, 2026');
});

it('formats monthly period label correctly', function (): void {
    $data = new PrayerSummaryData(
        periodType: 'monthly',
        periodStart: Carbon::parse('2026-02-01'),
        periodEnd: Carbon::parse('2026-02-28'),
        categoryBreakdown: [],
        urgencyBreakdown: [],
        summaryText: '',
        keyThemes: [],
        pastoralRecommendations: [],
        totalRequests: 0,
        answeredRequests: 0,
        criticalRequests: 0,
    );

    expect($data->periodLabel())->toBe('February 2026');
});

// ============================================
// SERVICE TESTS
// ============================================

it('generates summary for branch with no prayers', function (): void {
    $periodStart = Carbon::parse('2026-02-01');
    $periodEnd = Carbon::parse('2026-02-07');

    $result = $this->service->generateSummary(
        $this->branch,
        'weekly',
        $periodStart,
        $periodEnd
    );

    expect($result)->toBeInstanceOf(PrayerSummaryData::class);
    expect($result->totalRequests)->toBe(0);
    expect($result->categoryBreakdown)->toBeEmpty();
    expect($result->summaryText)->toContain('No prayer requests');
});

it('generates summary with correct category breakdown', function (): void {
    // Create prayers in different categories
    PrayerRequest::factory()->for($this->branch)->create([
        'category' => PrayerRequestCategory::Health,
        'submitted_at' => Carbon::parse('2026-02-03'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'category' => PrayerRequestCategory::Health,
        'submitted_at' => Carbon::parse('2026-02-04'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'category' => PrayerRequestCategory::Family,
        'submitted_at' => Carbon::parse('2026-02-05'),
    ]);

    $periodStart = Carbon::parse('2026-02-01');
    $periodEnd = Carbon::parse('2026-02-07');

    $result = $this->service->generateSummary(
        $this->branch,
        'weekly',
        $periodStart,
        $periodEnd
    );

    expect($result->totalRequests)->toBe(3);
    expect($result->categoryBreakdown)->toHaveKey('health');
    expect($result->categoryBreakdown['health'])->toBe(2);
    expect($result->categoryBreakdown['family'])->toBe(1);
});

it('generates summary with correct urgency breakdown', function (): void {
    PrayerRequest::factory()->for($this->branch)->create([
        'urgency_level' => PrayerUrgencyLevel::Normal,
        'submitted_at' => Carbon::parse('2026-02-03'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'urgency_level' => PrayerUrgencyLevel::High,
        'submitted_at' => Carbon::parse('2026-02-04'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'urgency_level' => PrayerUrgencyLevel::Critical,
        'submitted_at' => Carbon::parse('2026-02-05'),
    ]);

    $periodStart = Carbon::parse('2026-02-01');
    $periodEnd = Carbon::parse('2026-02-07');

    $result = $this->service->generateSummary(
        $this->branch,
        'weekly',
        $periodStart,
        $periodEnd
    );

    expect($result->criticalRequests)->toBe(2); // High + Critical
    expect($result->urgencyBreakdown)->toHaveKey('normal');
    expect($result->urgencyBreakdown)->toHaveKey('high');
    expect($result->urgencyBreakdown)->toHaveKey('critical');
});

it('counts answered requests correctly', function (): void {
    PrayerRequest::factory()->for($this->branch)->create([
        'status' => PrayerRequestStatus::Open,
        'submitted_at' => Carbon::parse('2026-02-03'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'status' => PrayerRequestStatus::Answered,
        'submitted_at' => Carbon::parse('2026-02-04'),
    ]);
    PrayerRequest::factory()->for($this->branch)->create([
        'status' => PrayerRequestStatus::Answered,
        'submitted_at' => Carbon::parse('2026-02-05'),
    ]);

    $periodStart = Carbon::parse('2026-02-01');
    $periodEnd = Carbon::parse('2026-02-07');

    $result = $this->service->generateSummary(
        $this->branch,
        'weekly',
        $periodStart,
        $periodEnd
    );

    expect($result->totalRequests)->toBe(3);
    expect($result->answeredRequests)->toBe(2);
    expect($result->answerRate())->toBe(66.7);
});

it('only includes prayers within the period', function (): void {
    // Inside period
    PrayerRequest::factory()->for($this->branch)->create([
        'submitted_at' => Carbon::parse('2026-02-03'),
    ]);

    // Outside period (before)
    PrayerRequest::factory()->for($this->branch)->create([
        'submitted_at' => Carbon::parse('2026-01-15'),
    ]);

    // Outside period (after)
    PrayerRequest::factory()->for($this->branch)->create([
        'submitted_at' => Carbon::parse('2026-02-15'),
    ]);

    $periodStart = Carbon::parse('2026-02-01');
    $periodEnd = Carbon::parse('2026-02-07');

    $result = $this->service->generateSummary(
        $this->branch,
        'weekly',
        $periodStart,
        $periodEnd
    );

    expect($result->totalRequests)->toBe(1);
});

// ============================================
// MODEL TESTS
// ============================================

it('creates prayer summary model', function (): void {
    $summary = PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => ['health' => 5],
        'urgency_breakdown' => ['normal' => 5],
        'summary_text' => 'Test summary',
        'key_themes' => ['Theme 1'],
        'pastoral_recommendations' => ['Recommendation 1'],
        'total_requests' => 5,
        'answered_requests' => 2,
        'critical_requests' => 0,
    ]);

    expect($summary)->toBeInstanceOf(PrayerSummary::class);
    expect($summary->branch_id)->toBe($this->branch->id);
    expect($summary->period_type)->toBe('weekly');
    expect($summary->total_requests)->toBe(5);
    expect($summary->category_breakdown)->toBe(['health' => 5]);
});

it('casts dates correctly', function (): void {
    $summary = PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Test',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 0,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    expect($summary->period_start)->toBeInstanceOf(Carbon::class);
    expect($summary->period_end)->toBeInstanceOf(Carbon::class);
});

it('scopes weekly summaries', function (): void {
    PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Weekly',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 5,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'monthly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-28',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Monthly',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 20,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    $weeklySummaries = PrayerSummary::weekly()->get();
    $monthlySummaries = PrayerSummary::monthly()->get();

    expect($weeklySummaries)->toHaveCount(1);
    expect($weeklySummaries->first()->summary_text)->toBe('Weekly');
    expect($monthlySummaries)->toHaveCount(1);
    expect($monthlySummaries->first()->summary_text)->toBe('Monthly');
});

it('calculates answer rate attribute', function (): void {
    $summary = PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Test',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 10,
        'answered_requests' => 3,
        'critical_requests' => 0,
    ]);

    expect($summary->answer_rate)->toBe(30.0);
});

it('generates period label attribute', function (): void {
    $weeklySummary = PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Test',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 0,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    expect($weeklySummary->period_label)->toBe('Feb 1 - Feb 7, 2026');
});

// ============================================
// JOB TESTS
// ============================================

it('dispatches job and creates summary', function (): void {
    config(['ai.features.prayer_analysis.enabled' => true]);

    PrayerRequest::factory()->for($this->branch)->create([
        'category' => PrayerRequestCategory::Health,
        'submitted_at' => Carbon::parse('2026-02-03'),
    ]);

    $job = new GeneratePrayerSummaryJob(
        $this->branch->id,
        'weekly',
        '2026-02-01',
        '2026-02-07',
        false
    );

    dispatch_sync($job);

    $summary = PrayerSummary::where('branch_id', $this->branch->id)->first();

    expect($summary)->not->toBeNull();
    expect($summary->period_type)->toBe('weekly');
    expect($summary->total_requests)->toBe(1);
});

it('skips job when feature is disabled', function (): void {
    config(['ai.features.prayer_analysis.enabled' => false]);

    $job = new GeneratePrayerSummaryJob(
        $this->branch->id,
        'weekly',
        '2026-02-01',
        '2026-02-07',
        false
    );

    dispatch_sync($job);

    expect(PrayerSummary::where('branch_id', $this->branch->id)->count())->toBe(0);
});

it('does not overwrite existing summary without flag', function (): void {
    config(['ai.features.prayer_analysis.enabled' => true]);

    // Create existing summary
    PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Original summary',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 99,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    $job = new GeneratePrayerSummaryJob(
        $this->branch->id,
        'weekly',
        '2026-02-01',
        '2026-02-07',
        false
    );

    dispatch_sync($job);

    $summary = PrayerSummary::where('branch_id', $this->branch->id)->first();

    expect($summary->summary_text)->toBe('Original summary');
    expect($summary->total_requests)->toBe(99);
});

it('overwrites existing summary with flag', function (): void {
    config(['ai.features.prayer_analysis.enabled' => true]);

    // Create existing summary
    PrayerSummary::create([
        'branch_id' => $this->branch->id,
        'period_type' => 'weekly',
        'period_start' => '2026-02-01',
        'period_end' => '2026-02-07',
        'category_breakdown' => [],
        'urgency_breakdown' => [],
        'summary_text' => 'Original summary',
        'key_themes' => [],
        'pastoral_recommendations' => [],
        'total_requests' => 99,
        'answered_requests' => 0,
        'critical_requests' => 0,
    ]);

    $job = new GeneratePrayerSummaryJob(
        $this->branch->id,
        'weekly',
        '2026-02-01',
        '2026-02-07',
        true // overwrite flag
    );

    dispatch_sync($job);

    $summary = PrayerSummary::where('branch_id', $this->branch->id)->first();

    // Should have been regenerated with actual data (0 requests in this period)
    expect($summary->total_requests)->toBe(0);
});
