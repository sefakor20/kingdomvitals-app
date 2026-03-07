<?php

declare(strict_types=1);

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Enums\BranchRole;
use App\Livewire\AI\InsightsDashboard;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// BASIC RENDERING
// ============================================

it('renders the insights dashboard', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('AI Insights Dashboard');
});

it('displays filters section', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertSee('Filters')
        ->assertSee('Last 7 days')
        ->assertSee('Export');
});

// ============================================
// FILTER FUNCTIONALITY
// ============================================

it('has default filter values', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertSet('dateRange', '7')
        ->assertSet('alertTypeFilter', '')
        ->assertSet('alertSeverityFilter', '')
        ->assertSet('alertStatusFilter', '');
});

it('can change date range filter', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('dateRange', '30')
        ->assertSet('dateRange', '30')
        ->set('dateRange', '90')
        ->assertSet('dateRange', '90');
});

it('can set alert type filter', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('alertTypeFilter', AiAlertType::ChurnRisk->value)
        ->assertSet('alertTypeFilter', AiAlertType::ChurnRisk->value);
});

it('can set alert severity filter', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('alertSeverityFilter', AlertSeverity::Critical->value)
        ->assertSet('alertSeverityFilter', AlertSeverity::Critical->value);
});

it('can set alert status filter', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('alertStatusFilter', 'unread')
        ->assertSet('alertStatusFilter', 'unread');
});

it('can reset filters', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('dateRange', '30')
        ->set('alertTypeFilter', AiAlertType::ChurnRisk->value)
        ->set('alertSeverityFilter', AlertSeverity::High->value)
        ->set('alertStatusFilter', 'unread')
        ->call('resetFilters')
        ->assertSet('dateRange', '7')
        ->assertSet('alertTypeFilter', '')
        ->assertSet('alertSeverityFilter', '')
        ->assertSet('alertStatusFilter', '');
});

// ============================================
// ALERTS DISPLAY
// ============================================

it('displays alerts when they exist', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'title' => 'Test Alert Title',
            'severity' => AlertSeverity::High,
        ]);

    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertSee('Test Alert Title')
        ->assertSee('AI Alerts');
});

it('filters alerts by type when filter is set', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'title' => 'Churn Risk Alert',
            'alert_type' => AiAlertType::ChurnRisk,
        ]);

    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'title' => 'Attendance Alert',
            'alert_type' => AiAlertType::AttendanceAnomaly,
        ]);

    // With filter - only matching type should be in the recentAlerts computed property
    $component = Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->set('alertTypeFilter', AiAlertType::ChurnRisk->value);

    // Verify filter is set
    $component->assertSet('alertTypeFilter', AiAlertType::ChurnRisk->value);

    // Verify the churn risk alert is shown
    $component->assertSee('Churn Risk Alert');
});

it('filters alerts by severity when filter is set', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'title' => 'Critical Alert',
            'severity' => AlertSeverity::Critical,
        ]);

    AiAlert::factory()
        ->for($this->branch)
        ->create([
            'title' => 'Low Alert',
            'severity' => AlertSeverity::Low,
        ]);

    $component = Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertSee('Critical Alert')
        ->assertSee('Low Alert');

    $component->set('alertSeverityFilter', AlertSeverity::Critical->value)
        ->assertSee('Critical Alert')
        ->assertDontSee('Low Alert');
});

// ============================================
// EXPORT FUNCTIONALITY
// ============================================

it('can export alerts to CSV', function (): void {
    AiAlert::factory()
        ->for($this->branch)
        ->count(3)
        ->create();

    $response = Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->call('exportAlertsCsv');

    expect($response->effects['download'])->not->toBeNull();
});

// ============================================
// ALERT ACTIONS
// ============================================

it('can mark alert as read', function (): void {
    $alert = AiAlert::factory()
        ->for($this->branch)
        ->create(['is_read' => false]);

    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->call('markAlertAsRead', $alert->id);

    expect($alert->fresh()->is_read)->toBeTrue();
});

it('can acknowledge alert', function (): void {
    $alert = AiAlert::factory()
        ->for($this->branch)
        ->create(['is_acknowledged' => false]);

    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->call('acknowledgeAlert', $alert->id);

    expect($alert->fresh()->is_acknowledged)->toBeTrue();
});

// ============================================
// COMPUTED DATA
// ============================================

it('displays trend visualizations section when data exists', function (): void {
    Livewire::actingAs($this->user)
        ->test(InsightsDashboard::class, ['branch' => $this->branch])
        ->assertSee('Total Members')
        ->assertSee('Total Clusters');
});
