<?php

declare(strict_types=1);

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\PrayerRequest;
use App\Services\AI\AiService;
use App\Services\AI\PrayerAnalysisService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new PrayerAnalysisService($aiService);
});

it('detects critical urgency for suicide keywords', function (): void {
    $content = 'I feel so hopeless, I want to end my life';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::Critical);
    expect($result['matched_pattern'])->toBe('critical');
    expect($result['keywords'])->not->toBeEmpty();
});

it('detects critical urgency for self-harm keywords', function (): void {
    $content = 'Please pray, I have been cutting myself and need help';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::Critical);
    expect($result['keywords'])->toContain('cutting');
});

it('detects high urgency for hospitalization', function (): void {
    $content = 'My mother is in the ICU after a heart attack';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::High);
    expect($result['matched_pattern'])->toBe('high');
});

it('detects high urgency for cancer diagnosis', function (): void {
    $content = 'We just got the news that dad has terminal cancer';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::High);
});

it('detects high urgency for divorce', function (): void {
    $content = 'My husband wants a divorce and I am devastated';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::High);
});

it('detects elevated urgency for depression', function (): void {
    $content = 'I have been really struggling with depression lately';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::Elevated);
    expect($result['matched_pattern'])->toBe('elevated');
});

it('detects elevated urgency for job loss', function (): void {
    $content = 'I was laid off from my job and need guidance';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::Elevated);
});

it('returns normal urgency for routine prayers', function (): void {
    $content = 'Please pray for safe travels this weekend';

    $result = $this->service->detectUrgency($content);

    expect($result['level'])->toBe(PrayerUrgencyLevel::Normal);
    expect($result['matched_pattern'])->toBeNull();
    expect($result['keywords'])->toBeEmpty();
});

it('suggests health category for medical content', function (): void {
    $content = 'Please pray for my surgery scheduled next week and quick recovery';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Health);
    expect($result['confidence'])->toBeGreaterThan(0);
    expect($result['keywords'])->not->toBeEmpty();
});

it('suggests family category for family content', function (): void {
    $content = 'Pray for my marriage and children as we work through some issues';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Family);
});

it('suggests finances category for financial content', function (): void {
    $content = 'Need prayer about our mortgage and paying off debt';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Finances);
});

it('suggests grief category for loss content', function (): void {
    $content = 'My grandmother passed away yesterday, need comfort during this loss';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Grief);
});

it('suggests guidance category for direction content', function (): void {
    $content = 'Seeking wisdom and discernment about an important decision';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Guidance);
});

it('suggests thanksgiving category for gratitude content', function (): void {
    $content = 'So grateful for this blessing, thanking God for answered prayer';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Thanksgiving);
});

it('returns other category when no keywords match', function (): void {
    $content = 'Just a random request without specific keywords';

    $result = $this->service->suggestCategory($content);

    expect($result['category'])->toBe(PrayerRequestCategory::Other);
    expect($result['confidence'])->toBe(0);
});

it('calculates base priority score', function (): void {
    $prayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $prayer->submitted_at = now();
    $prayer->created_at = now();
    $prayer->urgency_level = PrayerUrgencyLevel::Normal;

    $prayer->shouldReceive('isOpen')->andReturn(true);
    $prayer->shouldReceive('isAnonymous')->andReturn(true);
    $prayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $prayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $score = $this->service->calculatePriority($prayer);

    // Base 50 + recency bonus (10 for same day)
    expect($score)->toBeGreaterThanOrEqual(50);
    expect($score)->toBeLessThanOrEqual(100);
});

it('adds urgency weight to priority score', function (): void {
    $normalPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $normalPrayer->submitted_at = now();
    $normalPrayer->created_at = now();
    $normalPrayer->urgency_level = PrayerUrgencyLevel::Normal;
    $normalPrayer->shouldReceive('isOpen')->andReturn(true);
    $normalPrayer->shouldReceive('isAnonymous')->andReturn(true);
    $normalPrayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $normalPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $criticalPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $criticalPrayer->submitted_at = now();
    $criticalPrayer->created_at = now();
    $criticalPrayer->urgency_level = PrayerUrgencyLevel::Critical;
    $criticalPrayer->shouldReceive('isOpen')->andReturn(true);
    $criticalPrayer->shouldReceive('isAnonymous')->andReturn(true);
    $criticalPrayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $criticalPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $normalScore = $this->service->calculatePriority($normalPrayer);
    $criticalScore = $this->service->calculatePriority($criticalPrayer);

    expect($criticalScore)->toBeGreaterThan($normalScore);
    expect($criticalScore - $normalScore)->toBe(40.0); // Critical adds 40 points
});

it('adds bonus for non-anonymous prayers', function (): void {
    $anonPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $anonPrayer->submitted_at = now();
    $anonPrayer->created_at = now();
    $anonPrayer->urgency_level = PrayerUrgencyLevel::Normal;
    $anonPrayer->shouldReceive('isOpen')->andReturn(true);
    $anonPrayer->shouldReceive('isAnonymous')->andReturn(true);
    $anonPrayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $anonPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $namedPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $namedPrayer->submitted_at = now();
    $namedPrayer->created_at = now();
    $namedPrayer->urgency_level = PrayerUrgencyLevel::Normal;
    $namedPrayer->shouldReceive('isOpen')->andReturn(true);
    $namedPrayer->shouldReceive('isAnonymous')->andReturn(false);
    $namedPrayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $namedPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $anonScore = $this->service->calculatePriority($anonPrayer);
    $namedScore = $this->service->calculatePriority($namedPrayer);

    expect($namedScore)->toBeGreaterThan($anonScore);
    expect($namedScore - $anonScore)->toBe(2.0);
});

it('adds bonus for leaders only prayers', function (): void {
    $publicPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $publicPrayer->submitted_at = now();
    $publicPrayer->created_at = now();
    $publicPrayer->urgency_level = PrayerUrgencyLevel::Normal;
    $publicPrayer->shouldReceive('isOpen')->andReturn(true);
    $publicPrayer->shouldReceive('isAnonymous')->andReturn(true);
    $publicPrayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $publicPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $leadersPrayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $leadersPrayer->submitted_at = now();
    $leadersPrayer->created_at = now();
    $leadersPrayer->urgency_level = PrayerUrgencyLevel::Normal;
    $leadersPrayer->shouldReceive('isOpen')->andReturn(true);
    $leadersPrayer->shouldReceive('isAnonymous')->andReturn(true);
    $leadersPrayer->shouldReceive('isLeadersOnly')->andReturn(true);
    $leadersPrayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $publicScore = $this->service->calculatePriority($publicPrayer);
    $leadersScore = $this->service->calculatePriority($leadersPrayer);

    expect($leadersScore)->toBeGreaterThan($publicScore);
    expect($leadersScore - $publicScore)->toBe(3.0);
});

it('analyzes prayer request and returns full analysis', function (): void {
    $prayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $prayer->title = 'Prayer for Surgery';
    $prayer->description = 'Please pray for my upcoming surgery at the hospital';
    $prayer->submitted_at = now();
    $prayer->created_at = now();
    $prayer->urgency_level = null;

    $prayer->shouldReceive('isOpen')->andReturn(true);
    $prayer->shouldReceive('isAnonymous')->andReturn(false);
    $prayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $prayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $analysis = $this->service->analyze($prayer);

    expect($analysis->urgencyLevel)->toBe(PrayerUrgencyLevel::High);
    expect($analysis->suggestedCategory)->toBe(PrayerRequestCategory::Health);
    expect($analysis->priorityScore)->toBeGreaterThan(50);
    expect($analysis->detectedKeywords)->not->toBeEmpty();
    expect($analysis->provider)->toBe('heuristic');
    expect($analysis->model)->toBe('v1');
});

it('should escalate for critical urgency', function (): void {
    $prayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $prayer->title = 'Desperate';
    $prayer->description = 'I feel like I want to end my life';
    $prayer->submitted_at = now();
    $prayer->created_at = now();

    $prayer->shouldReceive('isOpen')->andReturn(true);
    $prayer->shouldReceive('isAnonymous')->andReturn(false);
    $prayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $prayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $analysis = $this->service->analyze($prayer);

    expect($analysis->urgencyLevel)->toBe(PrayerUrgencyLevel::Critical);
    expect($analysis->shouldEscalate())->toBeTrue();
});

it('should not escalate for normal urgency', function (): void {
    $prayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $prayer->title = 'Travel Prayer';
    $prayer->description = 'Please pray for safe travels this weekend';
    $prayer->submitted_at = now();
    $prayer->created_at = now();

    $prayer->shouldReceive('isOpen')->andReturn(true);
    $prayer->shouldReceive('isAnonymous')->andReturn(false);
    $prayer->shouldReceive('isLeadersOnly')->andReturn(false);
    $prayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $analysis = $this->service->analyze($prayer);

    expect($analysis->urgencyLevel)->toBe(PrayerUrgencyLevel::Normal);
    expect($analysis->shouldEscalate())->toBeFalse();
});

it('caps priority score at 100', function (): void {
    $prayer = Mockery::mock(PrayerRequest::class)->makePartial();
    $prayer->submitted_at = now();
    $prayer->created_at = now();
    $prayer->urgency_level = PrayerUrgencyLevel::Critical;

    $prayer->shouldReceive('isOpen')->andReturn(true);
    $prayer->shouldReceive('isAnonymous')->andReturn(false);
    $prayer->shouldReceive('isLeadersOnly')->andReturn(true);
    $prayer->shouldReceive('getAttribute')->with('updates')->andReturn(collect([]));

    $score = $this->service->calculatePriority($prayer);

    expect($score)->toBeLessThanOrEqual(100);
});

it('returns correct badge color for urgency levels', function (): void {
    expect(PrayerUrgencyLevel::Normal->color())->toBe('zinc');
    expect(PrayerUrgencyLevel::Elevated->color())->toBe('yellow');
    expect(PrayerUrgencyLevel::High->color())->toBe('amber');
    expect(PrayerUrgencyLevel::Critical->color())->toBe('red');
});

it('returns correct icon for urgency levels', function (): void {
    expect(PrayerUrgencyLevel::Normal->icon())->toBe('chat-bubble-left');
    expect(PrayerUrgencyLevel::Elevated->icon())->toBe('exclamation-circle');
    expect(PrayerUrgencyLevel::High->icon())->toBe('exclamation-triangle');
    expect(PrayerUrgencyLevel::Critical->icon())->toBe('bell-alert');
});

it('returns correct priority weight for urgency levels', function (): void {
    expect(PrayerUrgencyLevel::Normal->priorityWeight())->toBe(0);
    expect(PrayerUrgencyLevel::Elevated->priorityWeight())->toBe(15);
    expect(PrayerUrgencyLevel::High->priorityWeight())->toBe(30);
    expect(PrayerUrgencyLevel::Critical->priorityWeight())->toBe(40);
});
