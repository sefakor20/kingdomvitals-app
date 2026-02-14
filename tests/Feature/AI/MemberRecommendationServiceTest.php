<?php

declare(strict_types=1);

use App\Enums\ClusterHealthLevel;
use App\Enums\LifecycleStage;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\ClusterRecommendation;
use App\Services\AI\MemberRecommendationService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = new MemberRecommendationService(new AiService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('creates valid ClusterRecommendation DTO', function (): void {
    $recommendation = new ClusterRecommendation(
        clusterId: 'test-cluster-id',
        clusterName: 'Youth Fellowship',
        clusterType: 'cell_group',
        overallScore: 85.5,
        locationScore: 100,
        demographicsScore: 80,
        capacityScore: 75,
        healthScore: 90,
        lifecycleScore: 85,
        matchReasons: ['Same City', 'Age Match', 'Has Space'],
        currentMembers: 12,
        capacity: 20,
        meetingDay: 'Friday',
        meetingTime: '6:00 PM',
        meetingLocation: 'Community Center, Accra',
    );

    expect($recommendation->clusterId)->toBe('test-cluster-id');
    expect($recommendation->clusterName)->toBe('Youth Fellowship');
    expect($recommendation->scorePercentage())->toBe(86);
    expect($recommendation->hasCapacity())->toBeTrue();
    expect($recommendation->capacityLabel())->toBe('12/20');
    expect($recommendation->topMatchReasons(2))->toHaveCount(2);
    expect($recommendation->meetingInfo())->toBe('Friday at 6:00 PM');
});

it('returns empty array when feature is disabled', function (): void {
    config(['ai.features.member_recommendation.enabled' => false]);

    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $recommendations = $this->service->getRecommendations($member);

    expect($recommendations)->toBeEmpty();
});

it('excludes clusters member is already in', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $existingCluster = Cluster::factory()
        ->for($this->branch)
        ->create();

    $newCluster = Cluster::factory()
        ->for($this->branch)
        ->create();

    $member->clusters()->attach($existingCluster->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $recommendations = $this->service->getRecommendations($member);

    $recommendedIds = array_map(fn ($r) => $r->clusterId, $recommendations);

    expect($recommendedIds)->not->toContain($existingCluster->id);
});

it('excludes full clusters from recommendations', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $fullCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['capacity' => 5]);

    // Fill the cluster
    $members = Member::factory()
        ->count(5)
        ->for($this->branch, 'primaryBranch')
        ->create();

    foreach ($members as $m) {
        $fullCluster->members()->attach($m->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    $availableCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['capacity' => 20]);

    $recommendations = $this->service->getRecommendations($member);

    $recommendedIds = array_map(fn ($r) => $r->clusterId, $recommendations);

    expect($recommendedIds)->not->toContain($fullCluster->id);
    expect($recommendedIds)->toContain($availableCluster->id);
});

it('scores same city location higher', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'city' => 'Accra',
            'state' => 'Greater Accra',
        ]);

    $sameCityCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['meeting_location' => 'Community Hall, Accra']);

    $differentCityCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['meeting_location' => 'Church Building, Kumasi']);

    $recommendations = $this->service->getRecommendations($member);

    // Find recommendations for each cluster
    $sameCityRec = collect($recommendations)->firstWhere('clusterId', $sameCityCluster->id);
    $differentCityRec = collect($recommendations)->firstWhere('clusterId', $differentCityCluster->id);

    if ($sameCityRec && $differentCityRec) {
        expect($sameCityRec->locationScore)->toBeGreaterThan($differentCityRec->locationScore);
    }
});

it('considers cluster health in scoring', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $healthyCluster = Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_score' => 90,
            'health_level' => ClusterHealthLevel::Thriving,
        ]);

    $strugglingCluster = Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_score' => 25,
            'health_level' => ClusterHealthLevel::Struggling,
        ]);

    $recommendations = $this->service->getRecommendations($member);

    $healthyRec = collect($recommendations)->firstWhere('clusterId', $healthyCluster->id);
    $strugglingRec = collect($recommendations)->firstWhere('clusterId', $strugglingCluster->id);

    if ($healthyRec && $strugglingRec) {
        expect($healthyRec->healthScore)->toBeGreaterThan($strugglingRec->healthScore);
    }
});

it('respects max recommendations limit', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Create 10 clusters
    Cluster::factory()
        ->count(10)
        ->for($this->branch)
        ->create();

    config(['ai.features.member_recommendation.max_recommendations' => 3]);

    $recommendations = $this->service->getRecommendations($member);

    expect($recommendations)->toHaveCount(3);
});

it('orders recommendations by overall score descending', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'city' => 'Accra',
        ]);

    // Create clusters with different health scores
    Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_score' => 30,
            'meeting_location' => 'Kumasi',
        ]);

    Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_score' => 90,
            'meeting_location' => 'Accra Center',
        ]);

    Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_score' => 60,
            'meeting_location' => 'Tema',
        ]);

    $recommendations = $this->service->getRecommendations($member);

    if (count($recommendations) >= 2) {
        $scores = array_map(fn ($r) => $r->overallScore, $recommendations);
        $sortedScores = $scores;
        rsort($sortedScores);

        expect($scores)->toBe($sortedScores);
    }
});

it('generates appropriate match reasons', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'city' => 'Accra',
        ]);

    $cluster = Cluster::factory()
        ->for($this->branch)
        ->create([
            'meeting_location' => 'Community Hall, Accra',
            'capacity' => 20,
            'health_score' => 85,
        ]);

    $recommendations = $this->service->getRecommendations($member);

    $rec = collect($recommendations)->firstWhere('clusterId', $cluster->id);

    if ($rec) {
        expect($rec->matchReasons)->toBeArray();
        expect($rec->matchReasons)->not->toBeEmpty();
    }
});

it('converts DTO to array correctly', function (): void {
    $recommendation = new ClusterRecommendation(
        clusterId: 'test-id',
        clusterName: 'Test Cluster',
        clusterType: 'cell_group',
        overallScore: 75.5,
        locationScore: 80,
        demographicsScore: 70,
        capacityScore: 90,
        healthScore: 65,
        lifecycleScore: 72,
        matchReasons: ['Same City', 'Has Space'],
        currentMembers: 8,
        capacity: 15,
        meetingDay: 'Sunday',
        meetingTime: '2:00 PM',
        meetingLocation: 'Church Hall',
    );

    $array = $recommendation->toArray();

    expect($array)->toHaveKey('cluster_id');
    expect($array)->toHaveKey('cluster_name');
    expect($array)->toHaveKey('overall_score');
    expect($array)->toHaveKey('match_reasons');
    expect($array)->toHaveKey('score_percentage');
    expect($array)->toHaveKey('has_capacity');
    expect($array)->toHaveKey('capacity_label');
    expect($array['cluster_id'])->toBe('test-id');
    expect($array['score_percentage'])->toBe(76);
});

it('creates recommendation from array correctly', function (): void {
    $data = [
        'cluster_id' => 'test-id',
        'cluster_name' => 'Test Cluster',
        'cluster_type' => 'house_fellowship',
        'overall_score' => 82.3,
        'location_score' => 90,
        'demographics_score' => 75,
        'capacity_score' => 85,
        'health_score' => 80,
        'lifecycle_score' => 78,
        'match_reasons' => ['Age Match', 'Healthy Group'],
        'current_members' => 10,
        'capacity' => 25,
        'meeting_day' => 'Wednesday',
        'meeting_time' => '7:00 PM',
        'meeting_location' => 'Home',
    ];

    $recommendation = ClusterRecommendation::fromArray($data);

    expect($recommendation->clusterId)->toBe('test-id');
    expect($recommendation->clusterName)->toBe('Test Cluster');
    expect($recommendation->clusterType)->toBe('house_fellowship');
    expect($recommendation->overallScore)->toBe(82.3);
    expect($recommendation->matchReasons)->toBe(['Age Match', 'Healthy Group']);
});

it('returns empty recommendations when no eligible clusters exist', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Create only inactive clusters
    Cluster::factory()
        ->for($this->branch)
        ->create(['is_active' => false]);

    $recommendations = $this->service->getRecommendations($member);

    expect($recommendations)->toBeEmpty();
});

it('calculates capacity score correctly', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Cluster with lots of space
    $emptyCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['capacity' => 20]);

    // Cluster nearly full
    $nearlyFullCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['capacity' => 10]);

    $nearlyFullMembers = Member::factory()
        ->count(9)
        ->for($this->branch, 'primaryBranch')
        ->create();

    foreach ($nearlyFullMembers as $m) {
        $nearlyFullCluster->members()->attach($m->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    $recommendations = $this->service->getRecommendations($member);

    $emptyRec = collect($recommendations)->firstWhere('clusterId', $emptyCluster->id);
    $nearlyFullRec = collect($recommendations)->firstWhere('clusterId', $nearlyFullCluster->id);

    if ($emptyRec && $nearlyFullRec) {
        expect($emptyRec->capacityScore)->toBeGreaterThan($nearlyFullRec->capacityScore);
    }
});

it('considers lifecycle stage compatibility', function (): void {
    $newMember = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::NewMember,
        ]);

    $clusterWithEngagedMembers = Cluster::factory()
        ->for($this->branch)
        ->create();

    // Add engaged members to the cluster
    $engagedMembers = Member::factory()
        ->count(5)
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::Engaged,
        ]);

    foreach ($engagedMembers as $m) {
        $clusterWithEngagedMembers->members()->attach($m->id, [
            'role' => 'member',
            'joined_at' => now()->subMonths(6),
        ]);
    }

    $recommendations = $this->service->getRecommendations($newMember);

    $rec = collect($recommendations)->firstWhere('clusterId', $clusterWithEngagedMembers->id);

    if ($rec) {
        // New members should score well with engaged clusters
        expect($rec->lifecycleScore)->toBeGreaterThanOrEqual(70);
    }
});

it('handles clusters with no capacity limit', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $unlimitedCluster = Cluster::factory()
        ->for($this->branch)
        ->create(['capacity' => null]);

    $recommendations = $this->service->getRecommendations($member);

    $rec = collect($recommendations)->firstWhere('clusterId', $unlimitedCluster->id);

    expect($rec)->not->toBeNull();
    expect($rec->hasCapacity())->toBeTrue();
    expect($rec->capacityScore)->toBeGreaterThanOrEqual(80);
});

it('scores badge color correctly based on overall score', function (): void {
    $highScore = new ClusterRecommendation(
        clusterId: '1',
        clusterName: 'High',
        clusterType: 'cell_group',
        overallScore: 85,
        locationScore: 90,
        demographicsScore: 80,
        capacityScore: 85,
        healthScore: 85,
        lifecycleScore: 85,
        matchReasons: [],
        currentMembers: 5,
        capacity: 20,
        meetingDay: null,
        meetingTime: null,
        meetingLocation: null,
    );

    $mediumScore = new ClusterRecommendation(
        clusterId: '2',
        clusterName: 'Medium',
        clusterType: 'cell_group',
        overallScore: 65,
        locationScore: 60,
        demographicsScore: 70,
        capacityScore: 65,
        healthScore: 65,
        lifecycleScore: 65,
        matchReasons: [],
        currentMembers: 10,
        capacity: 20,
        meetingDay: null,
        meetingTime: null,
        meetingLocation: null,
    );

    $lowScore = new ClusterRecommendation(
        clusterId: '3',
        clusterName: 'Low',
        clusterType: 'cell_group',
        overallScore: 35,
        locationScore: 30,
        demographicsScore: 40,
        capacityScore: 35,
        healthScore: 35,
        lifecycleScore: 35,
        matchReasons: [],
        currentMembers: 15,
        capacity: 20,
        meetingDay: null,
        meetingTime: null,
        meetingLocation: null,
    );

    expect($highScore->scoreBadgeColor())->toBe('green');
    expect($mediumScore->scoreBadgeColor())->toBe('amber');
    expect($lowScore->scoreBadgeColor())->toBe('red'); // Score < 40 returns red
});
