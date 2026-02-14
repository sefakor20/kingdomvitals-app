<?php

declare(strict_types=1);

use App\Enums\HouseholdEngagementLevel;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\HouseholdEngagementAssessment;
use App\Services\AI\HouseholdEngagementService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new HouseholdEngagementService($aiService);
});

it('creates valid HouseholdEngagementAssessment DTO', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test-household-id',
        engagementScore: 75.5,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 80.0,
        givingScore: 70.0,
        memberVariance: 15.0,
        memberScores: ['member-1' => 80.0, 'member-2' => 70.0],
        factors: ['member_count' => 2],
        recommendations: ['Keep up the good work'],
    );

    expect($assessment->householdId)->toBe('test-household-id');
    expect($assessment->engagementScore)->toBe(75.5);
    expect($assessment->level)->toBe(HouseholdEngagementLevel::High);
    expect($assessment->attendanceScore)->toBe(80.0);
    expect($assessment->givingScore)->toBe(70.0);
    expect($assessment->memberVariance)->toBe(15.0);
    expect($assessment->memberScores)->toHaveCount(2);
});

it('identifies partially engaged households', function (): void {
    $partiallyEngaged = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 45.0,
        level: HouseholdEngagementLevel::PartiallyEngaged,
        attendanceScore: 50.0,
        givingScore: 40.0,
        memberVariance: 35.0,
        memberScores: [],
        factors: [],
    );

    $fullyEngaged = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 80.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 85.0,
        givingScore: 75.0,
        memberVariance: 5.0,
        memberScores: [],
        factors: [],
    );

    expect($partiallyEngaged->isPartiallyEngaged())->toBeTrue();
    expect($fullyEngaged->isPartiallyEngaged())->toBeFalse();
});

it('identifies disengaged members correctly', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 50.0,
        level: HouseholdEngagementLevel::PartiallyEngaged,
        attendanceScore: 50.0,
        givingScore: 50.0,
        memberVariance: 30.0,
        memberScores: [
            'member-1' => 90.0,
            'member-2' => 80.0,
            'member-3' => 30.0, // Below 60% of average (66.7)
        ],
        factors: [],
    );

    $disengaged = $assessment->getDisengagedMembers();

    expect($disengaged)->toContain('member-3');
    expect($disengaged)->not->toContain('member-1');
    expect($disengaged)->not->toContain('member-2');
});

it('identifies most engaged member', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 70.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 70.0,
        givingScore: 70.0,
        memberVariance: 10.0,
        memberScores: [
            'member-1' => 80.0,
            'member-2' => 90.0,
            'member-3' => 60.0,
        ],
        factors: [],
    );

    expect($assessment->getMostEngagedMember())->toBe('member-2');
});

it('returns correct variance levels', function (): void {
    $highVariance = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 50.0,
        level: HouseholdEngagementLevel::Medium,
        attendanceScore: 50.0,
        givingScore: 50.0,
        memberVariance: 45.0,
        memberScores: [],
        factors: [],
    );

    $mediumVariance = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 50.0,
        level: HouseholdEngagementLevel::Medium,
        attendanceScore: 50.0,
        givingScore: 50.0,
        memberVariance: 25.0,
        memberScores: [],
        factors: [],
    );

    $lowVariance = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 50.0,
        level: HouseholdEngagementLevel::Medium,
        attendanceScore: 50.0,
        givingScore: 50.0,
        memberVariance: 10.0,
        memberScores: [],
        factors: [],
    );

    expect($highVariance->varianceLevel())->toBe('high');
    expect($mediumVariance->varianceLevel())->toBe('medium');
    expect($lowVariance->varianceLevel())->toBe('low');
});

it('returns correct engagement level labels', function (): void {
    expect(HouseholdEngagementLevel::High->label())->toBe('Highly Engaged');
    expect(HouseholdEngagementLevel::Medium->label())->toBe('Moderately Engaged');
    expect(HouseholdEngagementLevel::Low->label())->toBe('Low Engagement');
    expect(HouseholdEngagementLevel::Disengaged->label())->toBe('Disengaged');
    expect(HouseholdEngagementLevel::PartiallyEngaged->label())->toBe('Partially Engaged');
});

it('returns correct engagement level colors', function (): void {
    expect(HouseholdEngagementLevel::High->color())->toBe('green');
    expect(HouseholdEngagementLevel::Medium->color())->toBe('blue');
    expect(HouseholdEngagementLevel::Low->color())->toBe('yellow');
    expect(HouseholdEngagementLevel::Disengaged->color())->toBe('zinc');
    expect(HouseholdEngagementLevel::PartiallyEngaged->color())->toBe('amber');
});

it('returns correct engagement level icons', function (): void {
    expect(HouseholdEngagementLevel::High->icon())->toBe('home');
    expect(HouseholdEngagementLevel::Medium->icon())->toBe('home-modern');
    expect(HouseholdEngagementLevel::Low->icon())->toBe('building-office');
    expect(HouseholdEngagementLevel::Disengaged->icon())->toBe('building-office-2');
    expect(HouseholdEngagementLevel::PartiallyEngaged->icon())->toBe('users');
});

it('correctly determines engagement level from score', function (): void {
    expect(HouseholdEngagementLevel::fromScore(80))->toBe(HouseholdEngagementLevel::High);
    expect(HouseholdEngagementLevel::fromScore(70))->toBe(HouseholdEngagementLevel::High);
    expect(HouseholdEngagementLevel::fromScore(50))->toBe(HouseholdEngagementLevel::Medium);
    expect(HouseholdEngagementLevel::fromScore(30))->toBe(HouseholdEngagementLevel::Low);
    expect(HouseholdEngagementLevel::fromScore(10))->toBe(HouseholdEngagementLevel::Disengaged);
});

it('correctly determines engagement level from score and variance', function (): void {
    // High variance + mid-range score = Partially Engaged
    expect(HouseholdEngagementLevel::fromScoreAndVariance(45, 35))->toBe(HouseholdEngagementLevel::PartiallyEngaged);

    // Low variance + high score = High
    expect(HouseholdEngagementLevel::fromScoreAndVariance(80, 10))->toBe(HouseholdEngagementLevel::High);

    // Low variance + low score = Low
    expect(HouseholdEngagementLevel::fromScoreAndVariance(25, 5))->toBe(HouseholdEngagementLevel::Low);
});

it('correctly identifies levels needing outreach', function (): void {
    expect(HouseholdEngagementLevel::Low->needsOutreach())->toBeTrue();
    expect(HouseholdEngagementLevel::Disengaged->needsOutreach())->toBeTrue();
    expect(HouseholdEngagementLevel::PartiallyEngaged->needsOutreach())->toBeTrue();
    expect(HouseholdEngagementLevel::High->needsOutreach())->toBeFalse();
    expect(HouseholdEngagementLevel::Medium->needsOutreach())->toBeFalse();
});

it('correctly identifies engaged levels', function (): void {
    expect(HouseholdEngagementLevel::High->isEngaged())->toBeTrue();
    expect(HouseholdEngagementLevel::Medium->isEngaged())->toBeTrue();
    expect(HouseholdEngagementLevel::Low->isEngaged())->toBeFalse();
    expect(HouseholdEngagementLevel::Disengaged->isEngaged())->toBeFalse();
    expect(HouseholdEngagementLevel::PartiallyEngaged->isEngaged())->toBeFalse();
});

it('correctly identifies engagement gap', function (): void {
    expect(HouseholdEngagementLevel::PartiallyEngaged->hasEngagementGap())->toBeTrue();
    expect(HouseholdEngagementLevel::High->hasEngagementGap())->toBeFalse();
    expect(HouseholdEngagementLevel::Low->hasEngagementGap())->toBeFalse();
});

it('converts assessment to array correctly', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test-id',
        engagementScore: 65.0,
        level: HouseholdEngagementLevel::Medium,
        attendanceScore: 70.0,
        givingScore: 60.0,
        memberVariance: 12.0,
        memberScores: ['m1' => 70.0],
        factors: ['member_count' => 1],
        recommendations: ['Keep engaging'],
    );

    $array = $assessment->toArray();

    expect($array)->toHaveKey('household_id');
    expect($array)->toHaveKey('engagement_score');
    expect($array)->toHaveKey('level');
    expect($array)->toHaveKey('attendance_score');
    expect($array)->toHaveKey('giving_score');
    expect($array)->toHaveKey('member_variance');
    expect($array)->toHaveKey('member_scores');
    expect($array)->toHaveKey('recommendations');
    expect($array)->toHaveKey('is_partially_engaged');
    expect($array['household_id'])->toBe('test-id');
    expect($array['level'])->toBe('medium');
});

it('creates assessment from array correctly', function (): void {
    $data = [
        'household_id' => 'test-id',
        'engagement_score' => 75.0,
        'level' => 'high',
        'attendance_score' => 80.0,
        'giving_score' => 70.0,
        'member_variance' => 8.0,
        'member_scores' => ['m1' => 75.0],
        'factors' => [],
        'recommendations' => [],
    ];

    $assessment = HouseholdEngagementAssessment::fromArray($data);

    expect($assessment->householdId)->toBe('test-id');
    expect($assessment->engagementScore)->toBe(75.0);
    expect($assessment->level)->toBe(HouseholdEngagementLevel::High);
    expect($assessment->attendanceScore)->toBe(80.0);
});

it('returns primary recommendation', function (): void {
    $withRecs = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 50.0,
        level: HouseholdEngagementLevel::Medium,
        attendanceScore: 50.0,
        givingScore: 50.0,
        memberVariance: 10.0,
        memberScores: [],
        factors: [],
        recommendations: ['First recommendation', 'Second recommendation'],
    );

    $noRecs = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 80.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 80.0,
        givingScore: 80.0,
        memberVariance: 5.0,
        memberScores: [],
        factors: [],
        recommendations: [],
    );

    expect($withRecs->primaryRecommendation())->toBe('First recommendation');
    expect($noRecs->primaryRecommendation())->toBeNull();
});

it('returns engagement gap description for partially engaged', function (): void {
    $partiallyEngaged = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 45.0,
        level: HouseholdEngagementLevel::PartiallyEngaged,
        attendanceScore: 50.0,
        givingScore: 40.0,
        memberVariance: 35.0,
        memberScores: ['m1' => 80.0, 'm2' => 20.0],
        factors: [],
    );

    $fullyEngaged = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 80.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 85.0,
        givingScore: 75.0,
        memberVariance: 5.0,
        memberScores: ['m1' => 80.0, 'm2' => 80.0],
        factors: [],
    );

    expect($partiallyEngaged->engagementGapDescription())->toContain('member(s)');
    expect($fullyEngaged->engagementGapDescription())->toBeNull();
});

it('returns badge color from level', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 75.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 75.0,
        givingScore: 75.0,
        memberVariance: 5.0,
        memberScores: [],
        factors: [],
    );

    expect($assessment->badgeColor())->toBe('green');
});

it('returns icon from level', function (): void {
    $assessment = new HouseholdEngagementAssessment(
        householdId: 'test',
        engagementScore: 75.0,
        level: HouseholdEngagementLevel::High,
        attendanceScore: 75.0,
        givingScore: 75.0,
        memberVariance: 5.0,
        memberScores: [],
        factors: [],
    );

    expect($assessment->icon())->toBe('home');
});
