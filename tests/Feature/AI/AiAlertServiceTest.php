<?php

declare(strict_types=1);

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\ClusterHealthLevel;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\PrayerUrgencyLevel;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\AiAlertSetting;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Services\AI\AiAlertService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = new AiAlertService;
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// CHURN RISK ALERTS
// ============================================

it('creates churn risk alerts for high-risk members', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    $alerts = $this->service->checkChurnRiskAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->alert_type)->toBe(AiAlertType::ChurnRisk);
    expect($alerts->first()->alertable_id)->toBe($member->id);
    expect($alerts->first()->severity)->toBe(AlertSeverity::High);
});

it('does not create churn alerts for members below threshold', function (): void {
    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 50]);

    $alerts = $this->service->checkChurnRiskAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

it('respects churn risk alert cooldown', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    // Create first alert
    $this->service->checkChurnRiskAlerts($this->branch);

    // Try to create second alert immediately
    $alerts = $this->service->checkChurnRiskAlerts($this->branch);

    // Should not create duplicate due to cooldown
    expect(AiAlert::where('alertable_id', $member->id)->count())->toBe(1);
});

// ============================================
// LIFECYCLE TRANSITION ALERTS
// ============================================

it('creates alerts for at-risk lifecycle transitions', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::AtRisk,
            'lifecycle_stage_changed_at' => now(),
        ]);

    $alerts = $this->service->checkLifecycleTransitionAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->alert_type)->toBe(AiAlertType::LifecycleChange);
    expect($alerts->first()->alertable_id)->toBe($member->id);
    expect($alerts->first()->severity)->toBe(AlertSeverity::High);
});

it('does not create alerts for old lifecycle transitions', function (): void {
    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::AtRisk,
            'lifecycle_stage_changed_at' => now()->subDays(30),
        ]);

    $alerts = $this->service->checkLifecycleTransitionAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

it('does not alert for healthy lifecycle stages', function (): void {
    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::Engaged,
            'lifecycle_stage_changed_at' => now(),
        ]);

    $alerts = $this->service->checkLifecycleTransitionAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

// ============================================
// CRITICAL PRAYER ALERTS
// ============================================

it('creates alerts for critical prayer requests', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $prayer = PrayerRequest::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'urgency_level' => PrayerUrgencyLevel::Critical,
            'status' => 'open',
        ]);

    $alerts = $this->service->checkCriticalPrayerAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->alert_type)->toBe(AiAlertType::CriticalPrayer);
    expect($alerts->first()->severity)->toBe(AlertSeverity::Critical);
});

it('creates alerts for high urgency prayer requests', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    PrayerRequest::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'urgency_level' => PrayerUrgencyLevel::High,
            'status' => 'open',
        ]);

    $alerts = $this->service->checkCriticalPrayerAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->severity)->toBe(AlertSeverity::High);
});

it('does not alert for normal urgency prayers', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    PrayerRequest::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'urgency_level' => PrayerUrgencyLevel::Normal,
            'status' => 'open',
        ]);

    $alerts = $this->service->checkCriticalPrayerAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

// ============================================
// CLUSTER HEALTH ALERTS
// ============================================

it('creates alerts for critical cluster health', function (): void {
    $cluster = Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_level' => ClusterHealthLevel::Critical->value,
            'health_score' => 25,
            'is_active' => true,
        ]);

    $alerts = $this->service->checkClusterHealthAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->alert_type)->toBe(AiAlertType::ClusterHealth);
    expect($alerts->first()->severity)->toBe(AlertSeverity::Critical);
});

it('creates alerts for struggling clusters', function (): void {
    Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_level' => ClusterHealthLevel::Struggling->value,
            'health_score' => 40,
            'is_active' => true,
        ]);

    $alerts = $this->service->checkClusterHealthAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->severity)->toBe(AlertSeverity::High);
});

it('does not alert for healthy clusters', function (): void {
    Cluster::factory()
        ->for($this->branch)
        ->create([
            'health_level' => ClusterHealthLevel::Thriving->value,
            'health_score' => 90,
            'is_active' => true,
        ]);

    $alerts = $this->service->checkClusterHealthAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

// ============================================
// HOUSEHOLD DISENGAGEMENT ALERTS
// ============================================

it('creates alerts for disengaged households', function (): void {
    Household::factory()
        ->for($this->branch)
        ->create([
            'engagement_level' => HouseholdEngagementLevel::Disengaged->value,
            'engagement_score' => 15,
        ]);

    $alerts = $this->service->checkHouseholdDisengagementAlerts($this->branch);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->alert_type)->toBe(AiAlertType::HouseholdDisengagement);
    expect($alerts->first()->severity)->toBe(AlertSeverity::Medium);
});

it('does not alert for engaged households', function (): void {
    Household::factory()
        ->for($this->branch)
        ->create([
            'engagement_level' => HouseholdEngagementLevel::High->value,
            'engagement_score' => 85,
        ]);

    $alerts = $this->service->checkHouseholdDisengagementAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

// ============================================
// PROCESS ALL ALERTS
// ============================================

it('processes all alert types at once', function (): void {
    // Create entities that should trigger alerts
    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 90]);

    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'lifecycle_stage' => LifecycleStage::Dormant,
            'lifecycle_stage_changed_at' => now(),
        ]);

    $alerts = $this->service->processAllAlerts($this->branch);

    expect($alerts->count())->toBeGreaterThanOrEqual(2);
});

// ============================================
// ALERT SETTINGS
// ============================================

it('respects disabled alert settings', function (): void {
    $setting = AiAlertSetting::getOrCreateForBranch(
        $this->branch->id,
        AiAlertType::ChurnRisk
    );
    $setting->update(['is_enabled' => false]);

    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 95]);

    $alerts = $this->service->checkChurnRiskAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

it('uses custom threshold from settings', function (): void {
    // Set threshold to 90
    $setting = AiAlertSetting::getOrCreateForBranch(
        $this->branch->id,
        AiAlertType::ChurnRisk
    );
    $setting->update(['threshold_value' => 90]);

    // Create member at 85 (below 90)
    Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['churn_risk_score' => 85]);

    $alerts = $this->service->checkChurnRiskAlerts($this->branch);

    expect($alerts)->toHaveCount(0);
});

// ============================================
// ALERT RETRIEVAL AND MANAGEMENT
// ============================================

it('retrieves recent alerts', function (): void {
    // Create multiple alerts
    AiAlert::factory()
        ->for($this->branch)
        ->count(5)
        ->create();

    $alerts = $this->service->getRecentAlerts($this->branch->id);

    expect($alerts)->toHaveCount(5);
});

it('counts unread alerts', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->count(3)
        ->create(['is_read' => false]);

    AiAlert::factory()
        ->for($this->branch)
        ->count(2)
        ->create(['is_read' => true]);

    expect($this->service->getUnreadCount($this->branch->id))->toBe(3);
});

it('retrieves high priority alerts', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Critical, 'is_acknowledged' => false]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Low, 'is_acknowledged' => false]);

    $alerts = $this->service->getHighPriorityAlerts($this->branch->id);

    expect($alerts)->toHaveCount(1);
    expect($alerts->first()->severity)->toBe(AlertSeverity::Critical);
});

it('marks alerts as read', function (): void {
    $alerts = AiAlert::factory()
        ->for($this->branch)
        ->count(3)
        ->create(['is_read' => false]);

    $this->service->markAlertsAsRead($alerts->pluck('id')->toArray());

    expect(AiAlert::where('is_read', false)->count())->toBe(0);
});

it('acknowledges alerts', function (): void {
    $user = \App\Models\User::factory()->create();

    $alert = AiAlert::factory()
        ->for($this->branch)
        ->create(['is_acknowledged' => false]);

    $result = $this->service->acknowledgeAlert($alert->id, $user->id);

    expect($result)->toBeTrue();
    expect($alert->fresh()->is_acknowledged)->toBeTrue();
    expect($alert->fresh()->acknowledged_by)->toBe($user->id);
});

it('gets alert statistics', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Critical, 'is_read' => false]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::High, 'is_read' => true]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Medium, 'is_read' => false]);

    $stats = $this->service->getAlertStats($this->branch->id);

    expect($stats['total'])->toBe(3);
    expect($stats['unread'])->toBe(2);
    expect($stats['critical'])->toBe(1);
    expect($stats['by_severity'])->toHaveKey('critical');
    expect($stats['by_severity'])->toHaveKey('high');
});

// ============================================
// ENUMS
// ============================================

it('provides correct alert type labels', function (): void {
    expect(AiAlertType::ChurnRisk->label())->toBe('Churn Risk Alert');
    expect(AiAlertType::AttendanceAnomaly->label())->toBe('Attendance Anomaly');
    expect(AiAlertType::LifecycleChange->label())->toBe('Lifecycle Transition');
    expect(AiAlertType::CriticalPrayer->label())->toBe('Critical Prayer Request');
    expect(AiAlertType::ClusterHealth->label())->toBe('Cluster Health Alert');
    expect(AiAlertType::HouseholdDisengagement->label())->toBe('Household Disengagement');
});

it('provides correct severity labels and colors', function (): void {
    expect(AlertSeverity::Critical->label())->toBe('Critical');
    expect(AlertSeverity::Critical->color())->toBe('red');
    expect(AlertSeverity::High->color())->toBe('orange');
    expect(AlertSeverity::Medium->color())->toBe('amber');
    expect(AlertSeverity::Low->color())->toBe('zinc');
});

it('identifies severities requiring immediate attention', function (): void {
    expect(AlertSeverity::Critical->requiresImmediateAttention())->toBeTrue();
    expect(AlertSeverity::High->requiresImmediateAttention())->toBeTrue();
    expect(AlertSeverity::Medium->requiresImmediateAttention())->toBeFalse();
    expect(AlertSeverity::Low->requiresImmediateAttention())->toBeFalse();
});

// ============================================
// MODEL SCOPES AND METHODS
// ============================================

it('filters alerts by severity scope', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Critical]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Low]);

    $highPriority = AiAlert::highPriority()->get();

    expect($highPriority)->toHaveCount(1);
});

it('orders alerts by severity', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Low]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Critical]);

    AiAlert::factory()
        ->for($this->branch)
        ->create(['severity' => AlertSeverity::Medium]);

    $alerts = AiAlert::orderBySeverity()->get();

    expect($alerts->first()->severity)->toBe(AlertSeverity::Critical);
});

it('checks for existing alerts within cooldown period', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Create an alert
    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'alert_type' => AiAlertType::ChurnRisk,
            'alertable_type' => Member::class,
            'alertable_id' => $member->id,
            'created_at' => now()->subHours(12),
        ]);

    $exists = AiAlert::existsForEntity(
        $this->branch->id,
        AiAlertType::ChurnRisk,
        Member::class,
        $member->id,
        24
    );

    expect($exists)->toBeTrue();
});

it('does not find alerts outside cooldown period', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'alert_type' => AiAlertType::ChurnRisk,
            'alertable_type' => Member::class,
            'alertable_id' => $member->id,
            'created_at' => now()->subHours(48),
        ]);

    $exists = AiAlert::existsForEntity(
        $this->branch->id,
        AiAlertType::ChurnRisk,
        Member::class,
        $member->id,
        24
    );

    expect($exists)->toBeFalse();
});
