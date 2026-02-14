<?php

declare(strict_types=1);

use App\Enums\LifecycleStage;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\LifecycleStageAssessment;
use App\Services\AI\MemberLifecycleService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new MemberLifecycleService($aiService);
});

it('creates valid LifecycleStageAssessment DTO', function (): void {
    $assessment = new LifecycleStageAssessment(
        memberId: 'test-member-id',
        stage: LifecycleStage::Growing,
        previousStage: LifecycleStage::NewMember,
        confidenceScore: 85.0,
        factors: ['growing' => ['description' => 'Regular attendance']],
    );

    expect($assessment->memberId)->toBe('test-member-id');
    expect($assessment->stage)->toBe(LifecycleStage::Growing);
    expect($assessment->previousStage)->toBe(LifecycleStage::NewMember);
    expect($assessment->confidenceScore)->toBe(85.0);
    expect($assessment->isTransition())->toBeTrue();
});

it('detects stage transition correctly', function (): void {
    $noTransition = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Growing,
        previousStage: LifecycleStage::Growing,
        confidenceScore: 80.0,
        factors: [],
    );

    $withTransition = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::AtRisk,
        previousStage: LifecycleStage::Engaged,
        confidenceScore: 80.0,
        factors: [],
    );

    expect($noTransition->isTransition())->toBeFalse();
    expect($withTransition->isTransition())->toBeTrue();
});

it('identifies concerning transitions to at-risk stages', function (): void {
    $concerning = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::AtRisk,
        previousStage: LifecycleStage::Engaged,
        confidenceScore: 80.0,
        factors: [],
    );

    $notConcerning = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: LifecycleStage::Growing,
        confidenceScore: 80.0,
        factors: [],
    );

    expect($concerning->isConcerningTransition())->toBeTrue();
    expect($notConcerning->isConcerningTransition())->toBeFalse();
});

it('identifies positive transitions to engaged stages', function (): void {
    $positive = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: LifecycleStage::NewMember,
        confidenceScore: 80.0,
        factors: [],
    );

    $notPositive = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Disengaging,
        previousStage: LifecycleStage::Engaged,
        confidenceScore: 80.0,
        factors: [],
    );

    expect($positive->isPositiveTransition())->toBeTrue();
    expect($notPositive->isPositiveTransition())->toBeFalse();
});

it('returns correct confidence levels', function (): void {
    $high = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: null,
        confidenceScore: 85.0,
        factors: [],
    );

    $medium = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: null,
        confidenceScore: 60.0,
        factors: [],
    );

    $low = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: null,
        confidenceScore: 30.0,
        factors: [],
    );

    expect($high->confidenceLevel())->toBe('high');
    expect($medium->confidenceLevel())->toBe('medium');
    expect($low->confidenceLevel())->toBe('low');
});

it('returns correct lifecycle stage labels', function (): void {
    expect(LifecycleStage::Prospect->label())->toBe('Prospect');
    expect(LifecycleStage::NewMember->label())->toBe('New Member');
    expect(LifecycleStage::Growing->label())->toBe('Growing');
    expect(LifecycleStage::Engaged->label())->toBe('Engaged');
    expect(LifecycleStage::Disengaging->label())->toBe('Disengaging');
    expect(LifecycleStage::AtRisk->label())->toBe('At Risk');
    expect(LifecycleStage::Dormant->label())->toBe('Dormant');
    expect(LifecycleStage::Inactive->label())->toBe('Inactive');
});

it('returns correct lifecycle stage colors', function (): void {
    expect(LifecycleStage::Prospect->color())->toBe('sky');
    expect(LifecycleStage::NewMember->color())->toBe('blue');
    expect(LifecycleStage::Growing->color())->toBe('cyan');
    expect(LifecycleStage::Engaged->color())->toBe('green');
    expect(LifecycleStage::Disengaging->color())->toBe('yellow');
    expect(LifecycleStage::AtRisk->color())->toBe('amber');
    expect(LifecycleStage::Dormant->color())->toBe('orange');
    expect(LifecycleStage::Inactive->color())->toBe('zinc');
});

it('returns correct lifecycle stage icons', function (): void {
    expect(LifecycleStage::Prospect->icon())->toBe('user-plus');
    expect(LifecycleStage::NewMember->icon())->toBe('sparkles');
    expect(LifecycleStage::Growing->icon())->toBe('arrow-trending-up');
    expect(LifecycleStage::Engaged->icon())->toBe('star');
    expect(LifecycleStage::Disengaging->icon())->toBe('arrow-trending-down');
    expect(LifecycleStage::AtRisk->icon())->toBe('exclamation-triangle');
    expect(LifecycleStage::Dormant->icon())->toBe('moon');
    expect(LifecycleStage::Inactive->icon())->toBe('x-circle');
});

it('correctly identifies stages needing attention', function (): void {
    expect(LifecycleStage::AtRisk->needsAttention())->toBeTrue();
    expect(LifecycleStage::Disengaging->needsAttention())->toBeTrue();
    expect(LifecycleStage::Dormant->needsAttention())->toBeTrue();
    expect(LifecycleStage::Engaged->needsAttention())->toBeFalse();
    expect(LifecycleStage::Growing->needsAttention())->toBeFalse();
    expect(LifecycleStage::NewMember->needsAttention())->toBeFalse();
});

it('correctly identifies active stages', function (): void {
    expect(LifecycleStage::NewMember->isActive())->toBeTrue();
    expect(LifecycleStage::Growing->isActive())->toBeTrue();
    expect(LifecycleStage::Engaged->isActive())->toBeTrue();
    expect(LifecycleStage::Disengaging->isActive())->toBeFalse();
    expect(LifecycleStage::AtRisk->isActive())->toBeFalse();
    expect(LifecycleStage::Dormant->isActive())->toBeFalse();
});

it('returns correct priority for lifecycle stages', function (): void {
    expect(LifecycleStage::AtRisk->priority())->toBe(100);
    expect(LifecycleStage::Disengaging->priority())->toBe(80);
    expect(LifecycleStage::Dormant->priority())->toBe(70);
    expect(LifecycleStage::Prospect->priority())->toBe(60);
    expect(LifecycleStage::NewMember->priority())->toBe(50);
    expect(LifecycleStage::Growing->priority())->toBe(30);
    expect(LifecycleStage::Engaged->priority())->toBe(10);
    expect(LifecycleStage::Inactive->priority())->toBe(0);
});

it('returns correct follow-up frequency in days', function (): void {
    expect(LifecycleStage::AtRisk->followUpFrequencyDays())->toBe(3);
    expect(LifecycleStage::Disengaging->followUpFrequencyDays())->toBe(7);
    expect(LifecycleStage::Dormant->followUpFrequencyDays())->toBe(14);
    expect(LifecycleStage::Prospect->followUpFrequencyDays())->toBe(7);
    expect(LifecycleStage::NewMember->followUpFrequencyDays())->toBe(14);
    expect(LifecycleStage::Growing->followUpFrequencyDays())->toBe(30);
    expect(LifecycleStage::Engaged->followUpFrequencyDays())->toBe(60);
    expect(LifecycleStage::Inactive->followUpFrequencyDays())->toBe(90);
});

it('returns declining stages correctly', function (): void {
    $declining = LifecycleStage::decliningStages();

    expect($declining)->toContain(LifecycleStage::Disengaging);
    expect($declining)->toContain(LifecycleStage::AtRisk);
    expect($declining)->toContain(LifecycleStage::Dormant);
    expect($declining)->not->toContain(LifecycleStage::Engaged);
});

it('returns engaged stages correctly', function (): void {
    $engaged = LifecycleStage::engagedStages();

    expect($engaged)->toContain(LifecycleStage::NewMember);
    expect($engaged)->toContain(LifecycleStage::Growing);
    expect($engaged)->toContain(LifecycleStage::Engaged);
    expect($engaged)->not->toContain(LifecycleStage::AtRisk);
});

it('converts assessment to array correctly', function (): void {
    $assessment = new LifecycleStageAssessment(
        memberId: 'test-id',
        stage: LifecycleStage::Growing,
        previousStage: LifecycleStage::NewMember,
        confidenceScore: 75.0,
        factors: ['test' => ['value' => 1]],
    );

    $array = $assessment->toArray();

    expect($array)->toHaveKey('member_id');
    expect($array)->toHaveKey('stage');
    expect($array)->toHaveKey('previous_stage');
    expect($array)->toHaveKey('confidence_score');
    expect($array)->toHaveKey('factors');
    expect($array)->toHaveKey('is_transition');
    expect($array)->toHaveKey('transition_description');
    expect($array['member_id'])->toBe('test-id');
    expect($array['stage'])->toBe('growing');
    expect($array['is_transition'])->toBeTrue();
});

it('creates assessment from array correctly', function (): void {
    $data = [
        'member_id' => 'test-id',
        'stage' => 'engaged',
        'previous_stage' => 'growing',
        'confidence_score' => 85.0,
        'factors' => ['test' => 'value'],
        'provider' => 'heuristic',
        'model' => 'v1',
    ];

    $assessment = LifecycleStageAssessment::fromArray($data);

    expect($assessment->memberId)->toBe('test-id');
    expect($assessment->stage)->toBe(LifecycleStage::Engaged);
    expect($assessment->previousStage)->toBe(LifecycleStage::Growing);
    expect($assessment->confidenceScore)->toBe(85.0);
});

it('returns transition description for transitions', function (): void {
    $withTransition = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: LifecycleStage::Growing,
        confidenceScore: 80.0,
        factors: [],
    );

    $noTransition = new LifecycleStageAssessment(
        memberId: 'test',
        stage: LifecycleStage::Engaged,
        previousStage: LifecycleStage::Engaged,
        confidenceScore: 80.0,
        factors: [],
    );

    expect($withTransition->transitionDescription())->toContain('Growing');
    expect($withTransition->transitionDescription())->toContain('Engaged');
    expect($noTransition->transitionDescription())->toBeNull();
});

it('correctly identifies prospect stage', function (): void {
    expect(LifecycleStage::Prospect->isPotentialMember())->toBeTrue();
    expect(LifecycleStage::NewMember->isPotentialMember())->toBeFalse();
    expect(LifecycleStage::Engaged->isPotentialMember())->toBeFalse();
});
