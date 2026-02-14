<?php

declare(strict_types=1);

use App\Models\Tenant\Visitor;
use App\Services\AI\AiService;
use App\Services\AI\VisitorConversionService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new VisitorConversionService($aiService);
});

it('returns base score for visitor with no activity', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = null;
    $visitor->visit_date = now()->subDays(5);
    $visitor->email = 'test@example.com';
    $visitor->phone = '1234567890';

    // Mock relationships
    $emptyCollection = collect([]);

    $visitor->shouldReceive('attendance->count')->andReturn(0);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(0);
    $visitor->shouldReceive('attendance->latest->first')->andReturn(null);

    $prediction = $this->service->calculateScore($visitor);

    // Base score is 50, plus 5 for complete contact info
    expect($prediction->score)->toBe(55.0);
    expect($prediction->provider)->toBe('heuristic');
    expect($prediction->model)->toBe('v1');
    expect($prediction->factors)->toHaveKey('contact_complete');
});

it('increases score for member referral', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = 'A friend invited me';
    $visitor->visit_date = now()->subDays(5);
    $visitor->email = 'test@example.com';
    $visitor->phone = '1234567890';

    $visitor->shouldReceive('attendance->count')->andReturn(0);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(0);
    $visitor->shouldReceive('attendance->latest->first')->andReturn(null);

    $prediction = $this->service->calculateScore($visitor);

    // Base 50 + referral bonus 10 + contact 5 = 65
    expect($prediction->score)->toBe(65.0);
    expect($prediction->factors)->toHaveKey('referral_source');
});

it('increases score for return visits', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = null;
    $visitor->visit_date = now()->subDays(10);
    $visitor->email = null;
    $visitor->phone = null;

    // 3 visits = 45 points (capped at 45)
    $visitor->shouldReceive('attendance->count')->andReturn(3);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(0);

    // Mock last attendance
    $lastAttendance = (object) ['date' => now()->subDays(3)];
    $visitor->shouldReceive('attendance->latest->first')->andReturn($lastAttendance);

    $prediction = $this->service->calculateScore($visitor);

    expect($prediction->factors)->toHaveKey('visit_count');
    expect($prediction->factors['visit_count']['value'])->toBe(3);
});

it('decreases score for old visitors without recent attendance', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = null;
    $visitor->visit_date = now()->subDays(30);
    $visitor->email = null;
    $visitor->phone = null;

    $visitor->shouldReceive('attendance->count')->andReturn(0);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(0);
    $visitor->shouldReceive('attendance->latest->first')->andReturn(null);

    $prediction = $this->service->calculateScore($visitor);

    expect($prediction->factors)->toHaveKey('time_since_visit');
    expect($prediction->score)->toBeLessThan(50); // Below base score
});

it('scores recent attendance highly', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = null;
    $visitor->visit_date = now()->subDays(10);
    $visitor->email = null;
    $visitor->phone = null;

    $visitor->shouldReceive('attendance->count')->andReturn(1);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(0);

    // Attended yesterday
    $lastAttendance = (object) ['date' => now()->subDays(1)];
    $visitor->shouldReceive('attendance->latest->first')->andReturn($lastAttendance);

    $prediction = $this->service->calculateScore($visitor);

    expect($prediction->factors)->toHaveKey('recent_attendance');
    expect($prediction->score)->toBeGreaterThan(50);
});

it('returns correct risk level for high score', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = 'Family member';
    $visitor->visit_date = now()->subDays(3);
    $visitor->email = 'test@example.com';
    $visitor->phone = '1234567890';

    // Many visits with successful follow-ups
    $visitor->shouldReceive('attendance->count')->andReturn(4);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(2);

    $lastAttendance = (object) ['date' => now()->subDays(2)];
    $visitor->shouldReceive('attendance->latest->first')->andReturn($lastAttendance);

    $prediction = $this->service->calculateScore($visitor);

    expect($prediction->score)->toBeGreaterThanOrEqual(70);
    expect($prediction->riskLevel())->toBe('high');
    expect($prediction->badgeVariant())->toBe('success');
});

it('caps score at 100', function (): void {
    $visitor = Mockery::mock(Visitor::class)->makePartial();
    $visitor->how_did_you_hear = 'Family member';
    $visitor->visit_date = now()->subDays(1);
    $visitor->email = 'test@example.com';
    $visitor->phone = '1234567890';

    // Maximum activity
    $visitor->shouldReceive('attendance->count')->andReturn(10);
    $visitor->shouldReceive('followUps->whereIn->count')->andReturn(5);

    $lastAttendance = (object) ['date' => now()];
    $visitor->shouldReceive('attendance->latest->first')->andReturn($lastAttendance);

    $prediction = $this->service->calculateScore($visitor);

    expect($prediction->score)->toBeLessThanOrEqual(100);
});
