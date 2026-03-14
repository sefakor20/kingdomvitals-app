<?php

declare(strict_types=1);

use App\Enums\EmploymentStatus;
use App\Enums\LifecycleStage;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\GivingCapacityService;
use App\Services\AI\GivingTrendService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->create();

    $aiService = new AiService;
    $givingTrendService = new GivingTrendService($aiService);
    $this->service = new GivingCapacityService($aiService, $givingTrendService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('estimates capacity based on profession', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Software Developer',
        'employment_status' => EmploymentStatus::Employed,
    ]);

    // Create some donations
    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subMonths(1),
        'amount' => 500,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors)->toHaveKey('profession');
    // Match keyword could be 'software' or 'developer'
    expect(['software', 'developer'])->toContain($assessment->factors['profession']['matched_keyword']);
    expect($assessment->provider)->toBe('heuristic');
});

it('uses default estimate for unknown professions', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Astronaut', // Not in the mapping
        'employment_status' => EmploymentStatus::Employed,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors['profession']['estimate'])->toBe(5000);
    expect($assessment->factors['profession']['matched_keyword'])->toBeNull();
});

it('adjusts capacity for retired status', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Teacher',
        'employment_status' => EmploymentStatus::Retired,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors['employment_status']['multiplier'])->toBe(0.6);
});

it('increases capacity for self-employed', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Consultant',
        'employment_status' => EmploymentStatus::SelfEmployed,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors['employment_status']['multiplier'])->toBe(1.1);
});

it('adjusts capacity for lifecycle stage', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Accountant',
        'lifecycle_stage' => LifecycleStage::Engaged,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors['lifecycle_stage']['multiplier'])->toBe(1.1);
});

it('reduces capacity estimate for inactive lifecycle', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Manager',
        'lifecycle_stage' => LifecycleStage::Inactive,
    ]);

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors['lifecycle_stage']['multiplier'])->toBe(0.2);
});

it('calculates capacity score based on actual vs potential giving', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Doctor', // High capacity ~15000
        'employment_status' => EmploymentStatus::Employed,
        'lifecycle_stage' => LifecycleStage::Engaged,
    ]);

    // Create modest donations (well under capacity)
    for ($i = 0; $i < 6; $i++) {
        Donation::factory()->for($member)->for($this->branch)->create([
            'donation_date' => now()->subMonths($i),
            'amount' => 200,
        ]);
    }

    $assessment = $this->service->assessCapacity($member);

    // Total annual giving ~2400 vs capacity ~16500, so score should be low
    expect($assessment->capacityScore)->toBeLessThan(50);
    expect($assessment->potentialGap)->toBeGreaterThan(0);
});

it('identifies high capacity score for members giving near potential', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Student', // Low capacity ~100
        'employment_status' => EmploymentStatus::Student,
        'lifecycle_stage' => LifecycleStage::Growing,
    ]);

    // Create donations matching their capacity
    for ($i = 0; $i < 12; $i++) {
        Donation::factory()->for($member)->for($this->branch)->create([
            'donation_date' => now()->subMonths($i),
            'amount' => 10,
        ]);
    }

    $assessment = $this->service->assessCapacity($member);

    // Student giving at capacity should have high utilization
    expect($assessment->capacityScore)->toBeGreaterThanOrEqual(50);
});

it('includes giving trajectory in factors', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'profession' => 'Engineer',
    ]);

    // Create increasing donations
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

    $assessment = $this->service->assessCapacity($member);

    expect($assessment->factors)->toHaveKey('giving_trajectory');
    expect($assessment->factors['giving_trajectory'])->toHaveKey('trend');
});

it('assesses capacity for all donating members in branch', function (): void {
    // Create members with donations
    for ($i = 0; $i < 3; $i++) {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'status' => 'active',
        ]);
        Donation::factory()->for($member)->for($this->branch)->create([
            'donation_date' => now()->subDays($i),
            'amount' => 100,
        ]);
    }

    // Create member without donations (should be excluded)
    Member::factory()->for($this->branch, 'primaryBranch')->create([
        'status' => 'active',
    ]);

    $assessments = $this->service->assessForBranch($this->branch);

    expect($assessments)->toHaveCount(3);
});

it('reports correct feature enabled status', function (): void {
    config(['ai.features.giving_capacity.enabled' => true]);
    expect($this->service->isEnabled())->toBeTrue();

    config(['ai.features.giving_capacity.enabled' => false]);
    expect($this->service->isEnabled())->toBeFalse();
});
