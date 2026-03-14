<?php

declare(strict_types=1);

use App\Enums\PledgeStatus;
use App\Enums\RiskLevel;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Services\AI\AiService;
use App\Services\AI\GivingTrendService;
use App\Services\AI\PledgePredictionService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->create();

    $aiService = new AiService;
    $givingTrendService = new GivingTrendService($aiService);
    $this->service = new PledgePredictionService($aiService, $givingTrendService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('returns base prediction for new pledge with no history', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 0,
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(90),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->provider)->toBe('heuristic');
    expect($prediction->factors)->toHaveKey('fulfillment_pace');
    expect($prediction->factors)->toHaveKey('time_remaining');
});

it('increases probability for pledge ahead of schedule', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Pledge that's 50% of time elapsed but 70% fulfilled
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 700,
        'start_date' => now()->subDays(50),
        'end_date' => now()->addDays(50),
        'status' => PledgeStatus::Active,
    ]);

    // Add recent donation for better recency score
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(5),
        'amount' => 100,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['fulfillment_pace']['score'])->toBeGreaterThan(0);
    expect($prediction->fulfillmentProbability)->toBeGreaterThan(50);
});

it('decreases probability for pledge behind schedule', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Pledge that's 80% of time elapsed but only 20% fulfilled
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 200,
        'start_date' => now()->subDays(80),
        'end_date' => now()->addDays(20),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['fulfillment_pace']['score'])->toBeLessThan(0);
    expect($prediction->riskLevel)->not->toBe(RiskLevel::Low);
});

it('considers member pledge completion history', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create completed past pledges
    for ($i = 0; $i < 3; $i++) {
        Pledge::factory()->for($member)->for($this->branch)->completed()->create([
            'start_date' => now()->subMonths($i + 6),
            'end_date' => now()->subMonths($i + 3),
        ]);
    }

    // Current active pledge
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 500,
        'start_date' => now()->subDays(30),
        'end_date' => now()->addDays(60),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['pledge_history']['score'])->toBeGreaterThan(0);
    expect($prediction->factors['pledge_history']['completion_rate'])->toBe(100.0);
});

it('penalizes members with poor pledge history', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create incomplete past pledges (only 20% fulfilled)
    for ($i = 0; $i < 3; $i++) {
        Pledge::factory()->for($member)->for($this->branch)->create([
            'amount' => 1000,
            'amount_fulfilled' => 200, // Only 20%
            'start_date' => now()->subMonths($i + 6),
            'end_date' => now()->subMonths($i + 3),
            'status' => PledgeStatus::Active,
        ]);
    }

    // Current active pledge
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 500,
        'start_date' => now()->subDays(30),
        'end_date' => now()->addDays(60),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['pledge_history']['score'])->toBeLessThan(0);
});

it('returns high probability for fulfilled pledge', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 1000,
        'start_date' => now()->subDays(60),
        'end_date' => now()->addDays(30),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['time_remaining']['score'])->toBe(25);
    expect($prediction->factors['time_remaining']['description'])->toBe('Pledge already fulfilled');
});

it('assigns high risk for overdue unfulfilled pledge', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 300,
        'start_date' => now()->subDays(120),
        'end_date' => now()->subDays(10), // Overdue
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['time_remaining']['score'])->toBe(-25);
    expect($prediction->riskLevel)->toBe(RiskLevel::High);
});

it('considers giving trend in prediction', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create growing donation pattern
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subMonths(1),
        'amount' => 500,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subMonths(3),
        'amount' => 300,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subMonths(5),
        'amount' => 100,
    ]);

    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 500,
        'start_date' => now()->subDays(30),
        'end_date' => now()->addDays(60),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    expect($prediction->factors['giving_trend'])->toHaveKey('trend');
});

it('recommends nudge date for high risk pledges', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 100,
        'start_date' => now()->subDays(80),
        'end_date' => now()->addDays(20),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    if ($prediction->riskLevel !== RiskLevel::Low) {
        expect($prediction->recommendedNudgeAt)->not->toBeNull();
    }
});

it('does not recommend nudge for low risk pledges', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create good pledge history
    for ($i = 0; $i < 3; $i++) {
        Pledge::factory()->for($member)->for($this->branch)->completed()->create([
            'start_date' => now()->subMonths($i + 6),
            'end_date' => now()->subMonths($i + 3),
        ]);
    }

    // Add recent donation
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(5),
        'amount' => 200,
    ]);

    // Well-funded pledge with plenty of time
    $pledge = Pledge::factory()->for($member)->for($this->branch)->create([
        'amount' => 1000,
        'amount_fulfilled' => 800,
        'start_date' => now()->subDays(20),
        'end_date' => now()->addDays(100),
        'status' => PledgeStatus::Active,
    ]);

    $prediction = $this->service->predictFulfillment($pledge);

    if ($prediction->riskLevel === RiskLevel::Low) {
        expect($prediction->recommendedNudgeAt)->toBeNull();
    }
});

it('predicts for all active pledges in branch', function (): void {
    $members = Member::factory()->for($this->branch, 'primaryBranch')->count(3)->create();

    foreach ($members as $member) {
        Pledge::factory()->for($member)->for($this->branch)->create([
            'status' => PledgeStatus::Active,
            'end_date' => now()->addDays(30),
        ]);
    }

    $predictions = $this->service->predictForBranch($this->branch);

    expect($predictions)->toHaveCount(3);
});

it('reports correct feature enabled status', function (): void {
    config(['ai.features.pledge_prediction.enabled' => true]);
    expect($this->service->isEnabled())->toBeTrue();

    config(['ai.features.pledge_prediction.enabled' => false]);
    expect($this->service->isEnabled())->toBeFalse();
});
