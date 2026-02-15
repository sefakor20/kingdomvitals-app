<?php

declare(strict_types=1);

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\LifecycleStage;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Services\AI\AlertRecommendationService;
use App\Services\AI\DTOs\AlertRecommendation;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = new AlertRecommendationService;
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// CHURN RISK RECOMMENDATIONS
// ============================================

it('returns recommendations for churn risk alerts', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ChurnRisk,
        'severity' => AlertSeverity::High,
        'title' => 'High churn risk',
        'description' => 'Member has high churn risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['churn_score' => 85, 'factors' => []],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    expect($recommendations)->toBeArray();
    expect($recommendations[0])->toBeInstanceOf(AlertRecommendation::class);
});

it('returns immediate priority for very high churn scores', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 90]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ChurnRisk,
        'severity' => AlertSeverity::Critical,
        'title' => 'Critical churn risk',
        'description' => 'Member has critical churn risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['churn_score' => 90, 'factors' => []],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    $immediateRecommendations = array_filter(
        $recommendations,
        fn (AlertRecommendation $r): bool => $r->priority === 'immediate'
    );

    expect($immediateRecommendations)->not->toBeEmpty();
});

it('includes giving-related recommendations when giving factors present', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 80]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ChurnRisk,
        'severity' => AlertSeverity::High,
        'title' => 'High churn risk',
        'description' => 'Member has high churn risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['churn_score' => 80, 'factors' => ['giving_decline']],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);

    expect($actions)->toContain('Review giving history');
});

// ============================================
// ATTENDANCE ANOMALY RECOMMENDATIONS
// ============================================

it('returns recommendations for attendance anomaly alerts', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::AttendanceAnomaly,
        'severity' => AlertSeverity::Medium,
        'title' => 'Attendance anomaly',
        'description' => 'Significant attendance change detected',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['anomaly_score' => 60],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);
    expect($actions)->toContain('Check on wellbeing');
});

// ============================================
// LIFECYCLE CHANGE RECOMMENDATIONS
// ============================================

it('returns at-risk recommendations for at-risk lifecycle stage', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['lifecycle_stage' => LifecycleStage::AtRisk]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::LifecycleChange,
        'severity' => AlertSeverity::High,
        'title' => 'Lifecycle transition',
        'description' => 'Member is now at-risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['current_stage' => 'at_risk', 'previous_stage' => 'engaged'],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);
    expect($actions)->toContain('Immediate pastoral contact');
});

it('returns dormant recommendations for dormant lifecycle stage', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['lifecycle_stage' => LifecycleStage::Dormant]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::LifecycleChange,
        'severity' => AlertSeverity::Medium,
        'title' => 'Lifecycle transition',
        'description' => 'Member is now dormant',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['current_stage' => 'dormant', 'previous_stage' => 'disengaging'],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);
    expect($actions)->toContain('Send re-engagement message');
});

// ============================================
// CRITICAL PRAYER RECOMMENDATIONS
// ============================================

it('returns immediate recommendations for critical prayer requests', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $prayer = PrayerRequest::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'urgency_level' => PrayerUrgencyLevel::Critical,
    ]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::CriticalPrayer,
        'severity' => AlertSeverity::Critical,
        'title' => 'Critical prayer request',
        'description' => 'Urgent prayer need',
        'alertable_type' => PrayerRequest::class,
        'alertable_id' => $prayer->id,
        'data' => ['urgency_level' => 'critical', 'category' => 'health'],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $immediateRecommendations = array_filter(
        $recommendations,
        fn (AlertRecommendation $r): bool => $r->priority === 'immediate'
    );
    expect($immediateRecommendations)->not->toBeEmpty();
});

// ============================================
// CLUSTER HEALTH RECOMMENDATIONS
// ============================================

it('returns recommendations for cluster health alerts', function (): void {
    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'health_score' => 35,
    ]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ClusterHealth,
        'severity' => AlertSeverity::High,
        'title' => 'Cluster health declining',
        'description' => 'Cluster is struggling',
        'alertable_type' => Cluster::class,
        'alertable_id' => $cluster->id,
        'data' => ['health_score' => 35, 'member_count' => 8],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);
    expect(in_array('Meet with cluster leader', $actions, true) || in_array('Connect with cluster leader', $actions, true))->toBeTrue();
});

it('suggests cluster division for large clusters', function (): void {
    $cluster = Cluster::factory()->create([
        'branch_id' => $this->branch->id,
        'health_score' => 40,
    ]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ClusterHealth,
        'severity' => AlertSeverity::High,
        'title' => 'Cluster health declining',
        'description' => 'Large cluster is struggling',
        'alertable_type' => Cluster::class,
        'alertable_id' => $cluster->id,
        'data' => ['health_score' => 40, 'member_count' => 15],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);

    expect($actions)->toContain('Consider cluster division');
});

// ============================================
// HOUSEHOLD DISENGAGEMENT RECOMMENDATIONS
// ============================================

it('returns recommendations for household disengagement alerts', function (): void {
    $household = Household::factory()->create([
        'branch_id' => $this->branch->id,
        'engagement_score' => 25,
    ]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::HouseholdDisengagement,
        'severity' => AlertSeverity::Medium,
        'title' => 'Household disengaged',
        'description' => 'Household engagement has dropped',
        'alertable_type' => Household::class,
        'alertable_id' => $household->id,
        'data' => ['engagement_score' => 25, 'member_count' => 4],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->not->toBeEmpty();
    $actions = array_map(fn (AlertRecommendation $r): string => $r->action, $recommendations);
    expect($actions)->toContain('Schedule family visit');
});

// ============================================
// CONFIGURATION
// ============================================

it('respects max_per_alert config setting', function (): void {
    config(['ai.features.recommendations.max_per_alert' => 2]);

    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ChurnRisk,
        'severity' => AlertSeverity::High,
        'title' => 'High churn risk',
        'description' => 'Member has high churn risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['churn_score' => 85, 'factors' => []],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->toHaveCount(2);
});

it('returns empty array when recommendations disabled', function (): void {
    config(['ai.features.recommendations.enabled' => false]);

    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    $alert = AiAlert::create([
        'branch_id' => $this->branch->id,
        'alert_type' => AiAlertType::ChurnRisk,
        'severity' => AlertSeverity::High,
        'title' => 'High churn risk',
        'description' => 'Member has high churn risk',
        'alertable_type' => Member::class,
        'alertable_id' => $member->id,
        'data' => ['churn_score' => 85, 'factors' => []],
    ]);

    $recommendations = $this->service->getRecommendationsForAlert($alert);

    expect($recommendations)->toBeEmpty();
});

// ============================================
// DTO METHODS
// ============================================

it('converts recommendations to storable format', function (): void {
    $recommendations = [
        new AlertRecommendation(
            action: 'Test action',
            description: 'Test description',
            priority: 'immediate',
            assignTo: 'pastor',
            icon: 'phone',
        ),
    ];

    $storable = $this->service->toStorableFormat($recommendations);

    expect($storable)->toBeArray();
    expect($storable[0])->toBeArray();
    expect($storable[0]['action'])->toBe('Test action');
    expect($storable[0]['priority'])->toBe('immediate');
    expect($storable[0]['assign_to'])->toBe('pastor');
});

it('restores recommendations from stored format', function (): void {
    $stored = [
        [
            'action' => 'Test action',
            'description' => 'Test description',
            'priority' => 'soon',
            'assign_to' => 'care_team',
            'icon' => 'heart',
        ],
    ];

    $recommendations = $this->service->fromStoredFormat($stored);

    expect($recommendations)->toHaveCount(1);
    expect($recommendations[0])->toBeInstanceOf(AlertRecommendation::class);
    expect($recommendations[0]->action)->toBe('Test action');
    expect($recommendations[0]->priority)->toBe('soon');
    expect($recommendations[0]->assignTo)->toBe('care_team');
});

// ============================================
// ALERT RECOMMENDATION DTO
// ============================================

it('returns correct priority labels', function (): void {
    $immediate = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'immediate',
    );

    $soon = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'soon',
    );

    $whenPossible = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'when_possible',
    );

    expect($immediate->priorityLabel())->toBe('Immediate');
    expect($soon->priorityLabel())->toBe('Within 48 Hours');
    expect($whenPossible->priorityLabel())->toBe('When Possible');
});

it('returns correct priority colors', function (): void {
    $immediate = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'immediate',
    );

    $soon = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'soon',
    );

    expect($immediate->priorityColor())->toBe('red');
    expect($soon->priorityColor())->toBe('amber');
});

it('checks if recommendation is immediate', function (): void {
    $immediate = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'immediate',
    );

    $soon = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'soon',
    );

    expect($immediate->isImmediate())->toBeTrue();
    expect($soon->isImmediate())->toBeFalse();
    expect($soon->isSoon())->toBeTrue();
});

it('returns assign to label', function (): void {
    $recommendation = new AlertRecommendation(
        action: 'Test',
        description: 'Test',
        priority: 'soon',
        assignTo: 'pastor',
    );

    expect($recommendation->assignToLabel())->toBe('Pastor');
});
