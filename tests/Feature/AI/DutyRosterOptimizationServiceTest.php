<?php

declare(strict_types=1);

use App\Enums\ExperienceLevel;
use App\Models\Tenant\DutyRosterPoolMember;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\MemberSuitabilityScore;
use App\Services\AI\DutyRosterOptimizationService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new DutyRosterOptimizationService($aiService);
});

it('calculates reliability score based on experience', function (): void {
    $poolMemberNovice = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
    $poolMemberNovice->experience_level = ExperienceLevel::Novice->value;
    $poolMemberNovice->assignment_count = 0;

    $poolMemberExpert = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
    $poolMemberExpert->experience_level = ExperienceLevel::Expert->value;
    $poolMemberExpert->assignment_count = 0;

    $noviceScore = $this->service->calculateReliabilityScore($poolMemberNovice);
    $expertScore = $this->service->calculateReliabilityScore($poolMemberExpert);

    expect($expertScore)->toBeGreaterThan($noviceScore);
});

it('calculates skill score based on experience level', function (): void {
    $poolMember = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
    $poolMember->experience_level = ExperienceLevel::Expert->value;

    $skillScore = $this->service->calculateSkillScore($poolMember);

    expect($skillScore)->toBe(95.0);
});

it('calculates correct skill score for all experience levels', function (): void {
    foreach (ExperienceLevel::cases() as $level) {
        $poolMember = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
        $poolMember->experience_level = $level->value;

        $skillScore = $this->service->calculateSkillScore($poolMember);

        $expectedScore = match ($level) {
            ExperienceLevel::Novice => 40.0,
            ExperienceLevel::Intermediate => 60.0,
            ExperienceLevel::Experienced => 80.0,
            ExperienceLevel::Expert => 95.0,
        };

        expect($skillScore)->toBe($expectedScore);
    }
});

it('returns correct experience priority weights', function (): void {
    expect(ExperienceLevel::Novice->priorityWeight())->toBe(0);
    expect(ExperienceLevel::Intermediate->priorityWeight())->toBe(5);
    expect(ExperienceLevel::Experienced->priorityWeight())->toBe(10);
    expect(ExperienceLevel::Expert->priorityWeight())->toBe(15);
});

it('returns correct experience levels', function (): void {
    expect(ExperienceLevel::Novice->level())->toBe(1);
    expect(ExperienceLevel::Intermediate->level())->toBe(2);
    expect(ExperienceLevel::Experienced->level())->toBe(3);
    expect(ExperienceLevel::Expert->level())->toBe(4);
});

it('checks experience requirements correctly', function (): void {
    expect(ExperienceLevel::Expert->meetsRequirement(ExperienceLevel::Novice))->toBeTrue();
    expect(ExperienceLevel::Expert->meetsRequirement(ExperienceLevel::Expert))->toBeTrue();
    expect(ExperienceLevel::Novice->meetsRequirement(ExperienceLevel::Expert))->toBeFalse();
    expect(ExperienceLevel::Intermediate->meetsRequirement(ExperienceLevel::Intermediate))->toBeTrue();
});

it('returns correct experience colors', function (): void {
    expect(ExperienceLevel::Novice->color())->toBe('zinc');
    expect(ExperienceLevel::Intermediate->color())->toBe('blue');
    expect(ExperienceLevel::Experienced->color())->toBe('green');
    expect(ExperienceLevel::Expert->color())->toBe('purple');
});

it('returns correct experience icons', function (): void {
    expect(ExperienceLevel::Novice->icon())->toBe('academic-cap');
    expect(ExperienceLevel::Intermediate->icon())->toBe('user');
    expect(ExperienceLevel::Experienced->icon())->toBe('user-circle');
    expect(ExperienceLevel::Expert->icon())->toBe('star');
});

it('returns correct experience labels', function (): void {
    expect(ExperienceLevel::Novice->label())->toBe('Novice');
    expect(ExperienceLevel::Intermediate->label())->toBe('Intermediate');
    expect(ExperienceLevel::Experienced->label())->toBe('Experienced');
    expect(ExperienceLevel::Expert->label())->toBe('Expert');
});

it('increases reliability score with higher experience', function (): void {
    $levels = ExperienceLevel::cases();
    $scores = [];

    foreach ($levels as $level) {
        $poolMember = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
        $poolMember->experience_level = $level->value;
        $poolMember->assignment_count = 5; // Same assignment count for fair comparison

        $scores[$level->value] = $this->service->calculateReliabilityScore($poolMember);
    }

    // Expert should have higher score than Novice
    expect($scores[ExperienceLevel::Expert->value])
        ->toBeGreaterThan($scores[ExperienceLevel::Novice->value]);

    // Scores should increase with experience level
    expect($scores[ExperienceLevel::Intermediate->value])
        ->toBeGreaterThan($scores[ExperienceLevel::Novice->value]);

    expect($scores[ExperienceLevel::Experienced->value])
        ->toBeGreaterThan($scores[ExperienceLevel::Intermediate->value]);
});

it('caps reliability score at 100', function (): void {
    $poolMember = Mockery::mock(DutyRosterPoolMember::class)->makePartial();
    $poolMember->experience_level = ExperienceLevel::Expert->value;
    $poolMember->assignment_count = 100; // High assignment count to test capping

    $reliabilityScore = $this->service->calculateReliabilityScore($poolMember);

    expect($reliabilityScore)->toBeLessThanOrEqual(100);
});

it('creates valid MemberSuitabilityScore DTO', function (): void {
    $score = new MemberSuitabilityScore(
        memberId: 'test-member-id',
        memberName: 'John Doe',
        totalScore: 75.5,
        factors: [
            'fairness' => 20.0,
            'experience' => 10.0,
            'reliability' => 12.5,
            'recency' => 8.0,
        ],
        isAvailable: true,
        warnings: [],
        experienceLevel: ExperienceLevel::Experienced,
    );

    expect($score->memberId)->toBe('test-member-id');
    expect($score->memberName)->toBe('John Doe');
    expect($score->totalScore)->toBe(75.5);
    expect($score->isAvailable)->toBeTrue();
    expect($score->warnings)->toBeEmpty();
    expect($score->experienceLevel)->toBe(ExperienceLevel::Experienced);
    expect($score->suitabilityLevel())->toBe('good');
    expect($score->badgeColor())->toBe('blue');
});

it('returns correct suitability levels for different scores', function (): void {
    // Excellent (80+)
    $excellent = new MemberSuitabilityScore(
        memberId: 'test',
        memberName: 'Test',
        totalScore: 85,
        factors: [],
        isAvailable: true,
        warnings: [],
        experienceLevel: ExperienceLevel::Expert,
    );
    expect($excellent->suitabilityLevel())->toBe('excellent');
    expect($excellent->badgeColor())->toBe('green');

    // Good (60-79)
    $good = new MemberSuitabilityScore(
        memberId: 'test',
        memberName: 'Test',
        totalScore: 70,
        factors: [],
        isAvailable: true,
        warnings: [],
        experienceLevel: ExperienceLevel::Intermediate,
    );
    expect($good->suitabilityLevel())->toBe('good');
    expect($good->badgeColor())->toBe('blue');

    // Fair (40-59)
    $fair = new MemberSuitabilityScore(
        memberId: 'test',
        memberName: 'Test',
        totalScore: 50,
        factors: [],
        isAvailable: true,
        warnings: [],
        experienceLevel: ExperienceLevel::Novice,
    );
    expect($fair->suitabilityLevel())->toBe('fair');
    expect($fair->badgeColor())->toBe('yellow');

    // Poor (<40)
    $poor = new MemberSuitabilityScore(
        memberId: 'test',
        memberName: 'Test',
        totalScore: 30,
        factors: [],
        isAvailable: true,
        warnings: [],
        experienceLevel: ExperienceLevel::Novice,
    );
    expect($poor->suitabilityLevel())->toBe('poor');
    expect($poor->badgeColor())->toBe('zinc');
});
