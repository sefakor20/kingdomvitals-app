<?php

declare(strict_types=1);

use App\Enums\LifecycleStage;
use App\Enums\PredictionTier;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\AI\AiService;
use App\Services\AI\EventPredictionService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->create();
    $this->service = new EventPredictionService(new AiService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('returns base score for member with no history', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'lifecycle_stage' => LifecycleStage::Growing,
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create();

    $prediction = $this->service->predictForMember($member, $event);

    // Base 50 + lifecycle growing (10) - recency penalty (15 for no attendance) = 45
    expect($prediction->probability)->toBeLessThan(60);
    expect($prediction->tier)->toBe(PredictionTier::Medium);
    expect($prediction->provider)->toBe('heuristic');
});

it('increases probability for engaged members', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'lifecycle_stage' => LifecycleStage::Engaged,
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create();

    $prediction = $this->service->predictForMember($member, $event);

    // Engaged members get +15 lifecycle bonus
    expect($prediction->factors)->toHaveKey('lifecycle_stage');
    expect($prediction->factors['lifecycle_stage']['score'])->toBe(15);
});

it('decreases probability for at-risk members', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'lifecycle_stage' => LifecycleStage::AtRisk,
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create();

    $prediction = $this->service->predictForMember($member, $event);

    // At-risk members get -10 adjustment
    expect($prediction->factors['lifecycle_stage']['score'])->toBe(-10);
});

it('returns high probability for already registered members', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();
    $event = Event::factory()->for($this->branch)->upcoming()->create();

    // Register the member for the event
    EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'branch_id' => $this->branch->id,
        'status' => 'registered',
        'registered_at' => now(),
    ]);

    $prediction = $this->service->predictForMember($member, $event);

    expect($prediction->probability)->toBe(95.0);
    expect($prediction->factors)->toHaveKey('already_registered');
});

it('adds proximity bonus when member city matches event city', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'city' => 'Accra',
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create([
        'city' => 'Accra',
    ]);

    $prediction = $this->service->predictForMember($member, $event);

    expect($prediction->factors)->toHaveKey('location_proximity');
    expect($prediction->factors['location_proximity']['score'])->toBe(10);
});

it('adds cluster bonus for cluster events when member is in cluster', function (): void {
    $cluster = Cluster::factory()->for($this->branch)->create();
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

    // Attach member to cluster
    $member->clusters()->attach($cluster->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create([
        'name' => 'Small Group Gathering',
    ]);

    $prediction = $this->service->predictForMember($member, $event);

    expect($prediction->factors)->toHaveKey('cluster_membership');
    expect($prediction->factors['cluster_membership']['score'])->toBe(15);
});

it('reduces recency penalty for recent attendees', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create();
    $event = Event::factory()->for($this->branch)->upcoming()->create();

    // Create recent attendance
    $testService = Service::factory()->for($this->branch)->create();
    Attendance::factory()->for($member)->for($this->branch)->create([
        'service_id' => $testService->id,
        'date' => now()->subDays(3),
    ]);

    $prediction = $this->service->predictForMember($member, $event);

    // Recent attendance should have minimal or no penalty
    expect($prediction->factors['recency']['score'] ?? 0)->toBeLessThanOrEqual(5);
});

it('caps probability at 100', function (): void {
    $cluster = Cluster::factory()->for($this->branch)->create();
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'lifecycle_stage' => LifecycleStage::Engaged,
        'city' => 'Accra',
    ]);

    // Attach member to cluster
    $member->clusters()->attach($cluster->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create([
        'name' => 'Cluster Fellowship Event',
        'city' => 'Accra',
    ]);

    // Create recent attendance to minimize penalty
    $testService = Service::factory()->for($this->branch)->create();
    Attendance::factory()->for($member)->for($this->branch)->create([
        'service_id' => $testService->id,
        'date' => now()->subDays(2),
    ]);

    $prediction = $this->service->predictForMember($member, $event);

    expect($prediction->probability)->toBeLessThanOrEqual(100);
});

it('returns predictions for all branch members', function (): void {
    Member::factory()->for($this->branch, 'primaryBranch')->count(3)->create([
        'status' => 'active',
        'lifecycle_stage' => LifecycleStage::Growing,
    ]);

    $event = Event::factory()->for($this->branch)->upcoming()->create();

    $predictions = $this->service->predictForEvent($event);

    expect($predictions)->toHaveCount(3);
    expect($predictions->first())->toBeInstanceOf(\App\Services\AI\DTOs\EventAttendancePrediction::class);
});

it('reports correct feature enabled status', function (): void {
    config(['ai.features.event_prediction.enabled' => true]);
    expect($this->service->isEnabled())->toBeTrue();

    config(['ai.features.event_prediction.enabled' => false]);
    expect($this->service->isEnabled())->toBeFalse();
});
