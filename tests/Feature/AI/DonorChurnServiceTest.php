<?php

declare(strict_types=1);

use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\DonorChurnService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    // Set up tenant context
    $this->setUpTestTenant();

    // Create branch
    $this->branch = Branch::factory()->create();

    $aiService = new AiService;
    $this->service = new DonorChurnService($aiService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('returns zero score for member with no donations', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    $assessment = $this->service->calculateScore($member);

    expect($assessment->score)->toBe(0.0);
    expect($assessment->daysSinceLastDonation)->toBeNull();
    expect($assessment->factors)->toHaveKey('no_donation_history');
    expect($assessment->provider)->toBe('heuristic');
});

it('calculates low score for recent regular donor', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create recent donations
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(7),
        'amount' => 100,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(37),
        'amount' => 100,
    ]);

    $assessment = $this->service->calculateScore($member);

    // Recent donor should have lower risk
    expect($assessment->score)->toBeLessThan(60);
    expect($assessment->daysSinceLastDonation)->toBe(7);
    expect($assessment->riskLevel())->toBe('low');
});

it('increases risk for inactive donors', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create donation from 150 days ago (well past 90 day threshold)
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(150),
        'amount' => 100,
    ]);

    $assessment = $this->service->calculateScore($member);

    expect($assessment->score)->toBeGreaterThan(50);
    expect($assessment->daysSinceLastDonation)->toBe(150);
    expect($assessment->factors)->toHaveKey('days_inactive');
});

it('increases risk when exceeding typical donation interval', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create historical monthly donations then stop
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(90),
        'amount' => 100,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(120),
        'amount' => 100,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(150),
        'amount' => 100,
    ]);

    $assessment = $this->service->calculateScore($member);

    // 90 days since last donation vs ~30 day interval = 3x exceeded
    // Should have elevated risk due to inactivity
    expect($assessment->score)->toBeGreaterThan(50);
    expect($assessment->riskLevel())->not->toBe('low');
});

it('decreases risk for increasing giving trend', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create increasing donations in recent months
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(15),
        'amount' => 500,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(45),
        'amount' => 400,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(75),
        'amount' => 300,
    ]);
    // Previous 3 months - lower amounts
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(100),
        'amount' => 100,
    ]);
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(130),
        'amount' => 100,
    ]);

    $assessment = $this->service->calculateScore($member);

    expect($assessment->factors)->toHaveKey('increasing_trend');
    expect($assessment->riskLevel())->toBe('low');
});

it('identifies at-risk regular donor who stopped', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Create 12+ donations (regular donor) that stopped 200 days ago
    for ($i = 0; $i < 13; $i++) {
        Donation::factory()->for($member)->for($this->branch)->create([
            'donation_date' => now()->subDays(200 + ($i * 30)),
            'amount' => 500,
        ]);
    }

    $assessment = $this->service->calculateScore($member);

    expect($assessment->score)->toBeGreaterThanOrEqual(70);
    expect($assessment->riskLevel())->toBe('high');
    expect($assessment->needsAttention())->toBeTrue();
    expect($assessment->badgeVariant())->toBe('danger');
});

it('caps score at 100', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Very old donation from a formerly regular donor
    for ($i = 0; $i < 15; $i++) {
        Donation::factory()->for($member)->for($this->branch)->create([
            'donation_date' => now()->subDays(365 + ($i * 7)),
            'amount' => 100,
        ]);
    }

    $assessment = $this->service->calculateScore($member);

    expect($assessment->score)->toBeLessThanOrEqual(100);
});
