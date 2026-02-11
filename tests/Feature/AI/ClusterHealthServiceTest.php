<?php

declare(strict_types=1);

use App\Enums\ClusterHealthLevel;
use App\Services\AI\AiService;
use App\Services\AI\ClusterHealthService;
use App\Services\AI\DTOs\ClusterHealthAssessment;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new ClusterHealthService($aiService);
});

it('creates valid ClusterHealthAssessment DTO', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test-cluster-id',
        clusterName: 'Test Cluster',
        overallScore: 75.5,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 80.0,
        engagementScore: 70.0,
        growthScore: 65.0,
        retentionScore: 85.0,
        leadershipScore: 75.0,
        factors: ['attendance' => ['score' => 80]],
        recommendations: ['Keep up the good work'],
        trends: ['direction' => 'stable'],
    );

    expect($assessment->clusterId)->toBe('test-cluster-id');
    expect($assessment->clusterName)->toBe('Test Cluster');
    expect($assessment->overallScore)->toBe(75.5);
    expect($assessment->level)->toBe(ClusterHealthLevel::Healthy);
    expect($assessment->attendanceScore)->toBe(80.0);
    expect($assessment->engagementScore)->toBe(70.0);
    expect($assessment->growthScore)->toBe(65.0);
    expect($assessment->retentionScore)->toBe(85.0);
    expect($assessment->leadershipScore)->toBe(75.0);
});

it('identifies clusters needing attention', function (): void {
    $struggling = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Struggling',
        overallScore: 35.0,
        level: ClusterHealthLevel::Struggling,
        attendanceScore: 30.0,
        engagementScore: 35.0,
        growthScore: 40.0,
        retentionScore: 35.0,
        leadershipScore: 30.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $healthy = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Healthy',
        overallScore: 70.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 70.0,
        engagementScore: 70.0,
        growthScore: 70.0,
        retentionScore: 70.0,
        leadershipScore: 70.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($struggling->needsAttention())->toBeTrue();
    expect($healthy->needsAttention())->toBeFalse();
});

it('identifies thriving clusters', function (): void {
    $thriving = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Thriving',
        overallScore: 90.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 90.0,
        engagementScore: 90.0,
        growthScore: 90.0,
        retentionScore: 90.0,
        leadershipScore: 90.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $healthy = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Healthy',
        overallScore: 70.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 70.0,
        engagementScore: 70.0,
        growthScore: 70.0,
        retentionScore: 70.0,
        leadershipScore: 70.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($thriving->isThriving())->toBeTrue();
    expect($healthy->isThriving())->toBeFalse();
});

it('identifies performing well clusters', function (): void {
    $thriving = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Thriving',
        overallScore: 90.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 90.0,
        engagementScore: 90.0,
        growthScore: 90.0,
        retentionScore: 90.0,
        leadershipScore: 90.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $healthy = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Healthy',
        overallScore: 70.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 70.0,
        engagementScore: 70.0,
        growthScore: 70.0,
        retentionScore: 70.0,
        leadershipScore: 70.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $struggling = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Struggling',
        overallScore: 35.0,
        level: ClusterHealthLevel::Struggling,
        attendanceScore: 30.0,
        engagementScore: 35.0,
        growthScore: 40.0,
        retentionScore: 35.0,
        leadershipScore: 30.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($thriving->isPerformingWell())->toBeTrue();
    expect($healthy->isPerformingWell())->toBeTrue();
    expect($struggling->isPerformingWell())->toBeFalse();
});

it('identifies top concerns correctly', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 50.0,
        level: ClusterHealthLevel::Stable,
        attendanceScore: 30.0, // Low - concern
        engagementScore: 70.0,
        growthScore: 25.0,     // Low - concern
        retentionScore: 80.0,
        leadershipScore: 60.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $concerns = $assessment->getTopConcerns();

    expect($concerns)->toHaveKey('growth');
    expect($concerns)->toHaveKey('attendance');
    expect($concerns)->not->toHaveKey('engagement');
    expect($concerns)->not->toHaveKey('retention');
});

it('identifies strengths correctly', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 60.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 30.0,
        engagementScore: 80.0,  // High - strength
        growthScore: 25.0,
        retentionScore: 90.0,   // High - strength
        leadershipScore: 50.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $strengths = $assessment->getStrengths();

    expect($strengths)->toHaveKey('retention');
    expect($strengths)->toHaveKey('engagement');
    expect($strengths)->not->toHaveKey('attendance');
    expect($strengths)->not->toHaveKey('growth');
});

it('identifies weakest and strongest areas', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 60.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 50.0,
        engagementScore: 80.0,
        growthScore: 30.0,
        retentionScore: 90.0,
        leadershipScore: 60.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($assessment->getWeakestArea())->toBe('growth');
    expect($assessment->getStrongestArea())->toBe('retention');
});

it('returns correct health level labels', function (): void {
    expect(ClusterHealthLevel::Thriving->label())->toBe('Thriving');
    expect(ClusterHealthLevel::Healthy->label())->toBe('Healthy');
    expect(ClusterHealthLevel::Stable->label())->toBe('Stable');
    expect(ClusterHealthLevel::Struggling->label())->toBe('Struggling');
    expect(ClusterHealthLevel::Critical->label())->toBe('Critical');
});

it('returns correct health level colors', function (): void {
    expect(ClusterHealthLevel::Thriving->color())->toBe('green');
    expect(ClusterHealthLevel::Healthy->color())->toBe('blue');
    expect(ClusterHealthLevel::Stable->color())->toBe('cyan');
    expect(ClusterHealthLevel::Struggling->color())->toBe('amber');
    expect(ClusterHealthLevel::Critical->color())->toBe('red');
});

it('returns correct health level icons', function (): void {
    expect(ClusterHealthLevel::Thriving->icon())->toBe('star');
    expect(ClusterHealthLevel::Healthy->icon())->toBe('check-circle');
    expect(ClusterHealthLevel::Stable->icon())->toBe('minus-circle');
    expect(ClusterHealthLevel::Struggling->icon())->toBe('exclamation-circle');
    expect(ClusterHealthLevel::Critical->icon())->toBe('exclamation-triangle');
});

it('correctly determines health level from score', function (): void {
    expect(ClusterHealthLevel::fromScore(85))->toBe(ClusterHealthLevel::Thriving);
    expect(ClusterHealthLevel::fromScore(80))->toBe(ClusterHealthLevel::Thriving);
    expect(ClusterHealthLevel::fromScore(70))->toBe(ClusterHealthLevel::Healthy);
    expect(ClusterHealthLevel::fromScore(60))->toBe(ClusterHealthLevel::Healthy);
    expect(ClusterHealthLevel::fromScore(50))->toBe(ClusterHealthLevel::Stable);
    expect(ClusterHealthLevel::fromScore(30))->toBe(ClusterHealthLevel::Struggling);
    expect(ClusterHealthLevel::fromScore(15))->toBe(ClusterHealthLevel::Critical);
});

it('returns correct score ranges', function (): void {
    expect(ClusterHealthLevel::Thriving->scoreRange())->toBe(['min' => 80, 'max' => 100]);
    expect(ClusterHealthLevel::Healthy->scoreRange())->toBe(['min' => 60, 'max' => 79]);
    expect(ClusterHealthLevel::Stable->scoreRange())->toBe(['min' => 40, 'max' => 59]);
    expect(ClusterHealthLevel::Struggling->scoreRange())->toBe(['min' => 20, 'max' => 39]);
    expect(ClusterHealthLevel::Critical->scoreRange())->toBe(['min' => 0, 'max' => 19]);
});

it('returns correct intervention priority', function (): void {
    expect(ClusterHealthLevel::Critical->interventionPriority())->toBe(100);
    expect(ClusterHealthLevel::Struggling->interventionPriority())->toBe(75);
    expect(ClusterHealthLevel::Stable->interventionPriority())->toBe(50);
    expect(ClusterHealthLevel::Healthy->interventionPriority())->toBe(25);
    expect(ClusterHealthLevel::Thriving->interventionPriority())->toBe(0);
});

it('returns correct check-in frequency in days', function (): void {
    expect(ClusterHealthLevel::Critical->checkInFrequencyDays())->toBe(7);
    expect(ClusterHealthLevel::Struggling->checkInFrequencyDays())->toBe(14);
    expect(ClusterHealthLevel::Stable->checkInFrequencyDays())->toBe(30);
    expect(ClusterHealthLevel::Healthy->checkInFrequencyDays())->toBe(45);
    expect(ClusterHealthLevel::Thriving->checkInFrequencyDays())->toBe(60);
});

it('correctly identifies levels needing attention', function (): void {
    expect(ClusterHealthLevel::Struggling->needsAttention())->toBeTrue();
    expect(ClusterHealthLevel::Critical->needsAttention())->toBeTrue();
    expect(ClusterHealthLevel::Stable->needsAttention())->toBeFalse();
    expect(ClusterHealthLevel::Healthy->needsAttention())->toBeFalse();
    expect(ClusterHealthLevel::Thriving->needsAttention())->toBeFalse();
});

it('correctly identifies levels performing well', function (): void {
    expect(ClusterHealthLevel::Thriving->isPerformingWell())->toBeTrue();
    expect(ClusterHealthLevel::Healthy->isPerformingWell())->toBeTrue();
    expect(ClusterHealthLevel::Stable->isPerformingWell())->toBeFalse();
    expect(ClusterHealthLevel::Struggling->isPerformingWell())->toBeFalse();
    expect(ClusterHealthLevel::Critical->isPerformingWell())->toBeFalse();
});

it('returns attention levels correctly', function (): void {
    $attention = ClusterHealthLevel::attentionLevels();

    expect($attention)->toContain(ClusterHealthLevel::Struggling);
    expect($attention)->toContain(ClusterHealthLevel::Critical);
    expect($attention)->not->toContain(ClusterHealthLevel::Stable);
    expect($attention)->not->toContain(ClusterHealthLevel::Healthy);
    expect($attention)->not->toContain(ClusterHealthLevel::Thriving);
});

it('converts assessment to array correctly', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test-id',
        clusterName: 'Test Cluster',
        overallScore: 65.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 70.0,
        engagementScore: 60.0,
        growthScore: 65.0,
        retentionScore: 70.0,
        leadershipScore: 60.0,
        factors: ['attendance' => ['score' => 70]],
        recommendations: ['Keep engaging'],
        trends: ['direction' => 'stable'],
    );

    $array = $assessment->toArray();

    expect($array)->toHaveKey('cluster_id');
    expect($array)->toHaveKey('cluster_name');
    expect($array)->toHaveKey('overall_score');
    expect($array)->toHaveKey('level');
    expect($array)->toHaveKey('attendance_score');
    expect($array)->toHaveKey('engagement_score');
    expect($array)->toHaveKey('growth_score');
    expect($array)->toHaveKey('retention_score');
    expect($array)->toHaveKey('leadership_score');
    expect($array)->toHaveKey('factors');
    expect($array)->toHaveKey('recommendations');
    expect($array)->toHaveKey('trends');
    expect($array)->toHaveKey('top_concerns');
    expect($array)->toHaveKey('strengths');
    expect($array)->toHaveKey('needs_attention');
    expect($array['cluster_id'])->toBe('test-id');
    expect($array['level'])->toBe('healthy');
});

it('creates assessment from array correctly', function (): void {
    $data = [
        'cluster_id' => 'test-id',
        'cluster_name' => 'Test Cluster',
        'overall_score' => 75.0,
        'level' => 'healthy',
        'attendance_score' => 80.0,
        'engagement_score' => 70.0,
        'growth_score' => 75.0,
        'retention_score' => 80.0,
        'leadership_score' => 70.0,
        'factors' => [],
        'recommendations' => [],
        'trends' => ['direction' => 'stable'],
    ];

    $assessment = ClusterHealthAssessment::fromArray($data);

    expect($assessment->clusterId)->toBe('test-id');
    expect($assessment->clusterName)->toBe('Test Cluster');
    expect($assessment->overallScore)->toBe(75.0);
    expect($assessment->level)->toBe(ClusterHealthLevel::Healthy);
    expect($assessment->attendanceScore)->toBe(80.0);
});

it('returns primary recommendation', function (): void {
    $withRecs = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 50.0,
        level: ClusterHealthLevel::Stable,
        attendanceScore: 50.0,
        engagementScore: 50.0,
        growthScore: 50.0,
        retentionScore: 50.0,
        leadershipScore: 50.0,
        factors: [],
        recommendations: ['First recommendation', 'Second recommendation'],
        trends: [],
    );

    $noRecs = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 80.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 80.0,
        engagementScore: 80.0,
        growthScore: 80.0,
        retentionScore: 80.0,
        leadershipScore: 80.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($withRecs->primaryRecommendation())->toBe('First recommendation');
    expect($noRecs->primaryRecommendation())->toBeNull();
});

it('detects trend direction correctly', function (): void {
    $declining = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 50.0,
        level: ClusterHealthLevel::Stable,
        attendanceScore: 50.0,
        engagementScore: 50.0,
        growthScore: 50.0,
        retentionScore: 50.0,
        leadershipScore: 50.0,
        factors: [],
        recommendations: [],
        trends: ['direction' => 'declining'],
    );

    $improving = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 60.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 60.0,
        engagementScore: 60.0,
        growthScore: 60.0,
        retentionScore: 60.0,
        leadershipScore: 60.0,
        factors: [],
        recommendations: [],
        trends: ['direction' => 'improving'],
    );

    $stable = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 70.0,
        level: ClusterHealthLevel::Healthy,
        attendanceScore: 70.0,
        engagementScore: 70.0,
        growthScore: 70.0,
        retentionScore: 70.0,
        leadershipScore: 70.0,
        factors: [],
        recommendations: [],
        trends: ['direction' => 'stable'],
    );

    expect($declining->isDeclining())->toBeTrue();
    expect($declining->isImproving())->toBeFalse();

    expect($improving->isImproving())->toBeTrue();
    expect($improving->isDeclining())->toBeFalse();

    expect($stable->isDeclining())->toBeFalse();
    expect($stable->isImproving())->toBeFalse();
});

it('returns badge color from level', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 90.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 90.0,
        engagementScore: 90.0,
        growthScore: 90.0,
        retentionScore: 90.0,
        leadershipScore: 90.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($assessment->badgeColor())->toBe('green');
});

it('returns icon from level', function (): void {
    $assessment = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 90.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 90.0,
        engagementScore: 90.0,
        growthScore: 90.0,
        retentionScore: 90.0,
        leadershipScore: 90.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($assessment->icon())->toBe('star');
});

it('returns check-in frequency from assessment', function (): void {
    $critical = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 15.0,
        level: ClusterHealthLevel::Critical,
        attendanceScore: 15.0,
        engagementScore: 15.0,
        growthScore: 15.0,
        retentionScore: 15.0,
        leadershipScore: 15.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    $thriving = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 90.0,
        level: ClusterHealthLevel::Thriving,
        attendanceScore: 90.0,
        engagementScore: 90.0,
        growthScore: 90.0,
        retentionScore: 90.0,
        leadershipScore: 90.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($critical->checkInFrequencyDays())->toBe(7);
    expect($thriving->checkInFrequencyDays())->toBe(60);
});

it('returns intervention priority from assessment', function (): void {
    $critical = new ClusterHealthAssessment(
        clusterId: 'test',
        clusterName: 'Test',
        overallScore: 15.0,
        level: ClusterHealthLevel::Critical,
        attendanceScore: 15.0,
        engagementScore: 15.0,
        growthScore: 15.0,
        retentionScore: 15.0,
        leadershipScore: 15.0,
        factors: [],
        recommendations: [],
        trends: [],
    );

    expect($critical->interventionPriority())->toBe(100);
});
