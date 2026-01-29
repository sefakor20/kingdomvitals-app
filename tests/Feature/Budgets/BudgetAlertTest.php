<?php

use App\Enums\BranchRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Expense;
use App\Models\User;
use App\Notifications\BudgetThresholdNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();

    // Create admin user
    $this->adminUser = User::factory()->create();
    $this->adminUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin->value,
    ]);

    // Create manager user
    $this->managerUser = User::factory()->create();
    $this->managerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager->value,
    ]);

    // Create staff user
    $this->staffUser = User::factory()->create();
    $this->staffUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff->value,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// Alert Factory State Tests

test('budget factory creates budget with default alert settings', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);

    expect($budget->alerts_enabled)->toBeTrue();
    expect($budget->alert_threshold_warning)->toBe(75);
    expect($budget->alert_threshold_critical)->toBe(90);
    expect($budget->last_warning_sent_at)->toBeNull();
    expect($budget->last_critical_sent_at)->toBeNull();
    expect($budget->last_exceeded_sent_at)->toBeNull();
});

test('budget factory withAlertsDisabled state works', function (): void {
    $budget = Budget::factory()->withAlertsDisabled()->create(['branch_id' => $this->branch->id]);
    expect($budget->alerts_enabled)->toBeFalse();
});

test('budget factory withCustomThresholds state works', function (): void {
    $budget = Budget::factory()->withCustomThresholds(60, 85)->create(['branch_id' => $this->branch->id]);
    expect($budget->alert_threshold_warning)->toBe(60);
    expect($budget->alert_threshold_critical)->toBe(85);
});

test('budget factory withWarningSentAt state works', function (): void {
    $date = now()->subDays(3);
    $budget = Budget::factory()->withWarningSentAt($date)->create(['branch_id' => $this->branch->id]);
    expect($budget->last_warning_sent_at->toDateString())->toBe($date->toDateString());
});

test('budget factory withCriticalSentAt state works', function (): void {
    $date = now()->subHours(12);
    $budget = Budget::factory()->withCriticalSentAt($date)->create(['branch_id' => $this->branch->id]);
    expect($budget->last_critical_sent_at->toDateString())->toBe($date->toDateString());
});

test('budget factory withExceededSentAt state works', function (): void {
    $date = now()->subHours(6);
    $budget = Budget::factory()->withExceededSentAt($date)->create(['branch_id' => $this->branch->id]);
    expect($budget->last_exceeded_sent_at->toDateString())->toBe($date->toDateString());
});

// Threshold Command Tests

test('command sends warning alert when budget reaches warning threshold', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'alert_threshold_warning' => 75,
        'alert_threshold_critical' => 90,
    ]);

    // Create expense that brings utilization to 80% (above warning threshold)
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertSentTo($this->adminUser, BudgetThresholdNotification::class);
    Notification::assertSentTo($this->managerUser, BudgetThresholdNotification::class);
    Notification::assertNotSentTo($this->staffUser, BudgetThresholdNotification::class);
});

test('command sends critical alert when budget reaches critical threshold', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'alert_threshold_warning' => 75,
        'alert_threshold_critical' => 90,
    ]);

    // Create expense that brings utilization to 95% (above critical threshold)
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 950.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertSentTo(
        $this->adminUser,
        BudgetThresholdNotification::class,
        function ($notification): bool {
            return $notification->alertLevel === 'critical';
        }
    );
});

test('command sends exceeded alert when budget exceeds 100%', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    // Create expense that exceeds budget
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 1200.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertSentTo(
        $this->adminUser,
        BudgetThresholdNotification::class,
        function ($notification): bool {
            return $notification->alertLevel === 'exceeded';
        }
    );
});

test('command does not send duplicate alerts within cooldown period', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'last_warning_sent_at' => now()->subHours(12), // Warning sent 12 hours ago
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    // Warning cooldown is 1 week, so no new alert should be sent
    Notification::assertNothingSent();
});

test('command sends alert after cooldown period expires', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'last_warning_sent_at' => now()->subDays(8), // Warning sent 8 days ago (past 1 week cooldown)
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertSentTo($this->adminUser, BudgetThresholdNotification::class);
});

test('command respects disabled alerts setting', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->withAlertsDisabled()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 1500.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertNothingSent();
});

test('command only processes active budgets', function (): void {
    Notification::fake();

    // Draft budget with exceeded spending
    Budget::factory()->draft()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 100.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    // Closed budget with exceeded spending
    Budget::factory()->closed()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Supplies,
        'allocated_amount' => 100.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 200.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Supplies,
        'amount' => 200.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertNothingSent();
});

test('command respects custom alert thresholds', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'alert_threshold_warning' => 50,
        'alert_threshold_critical' => 70,
    ]);

    // 60% utilization - above custom warning (50%) but below custom critical (70%)
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 600.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertSentTo(
        $this->adminUser,
        BudgetThresholdNotification::class,
        function ($notification): bool {
            return $notification->alertLevel === 'warning';
        }
    );
});

test('command updates last_sent_at timestamp after sending alert', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    expect($budget->last_warning_sent_at)->toBeNull();

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    // Re-initialize tenancy since the command ends it after processing
    tenancy()->initialize($this->tenant);

    $budget->refresh();
    expect($budget->last_warning_sent_at)->not->toBeNull();
});

test('command does not send alert when utilization is below warning threshold', function (): void {
    Notification::fake();

    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'alert_threshold_warning' => 75,
    ]);

    // Only 50% utilization - below warning threshold
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 500.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    Notification::assertNothingSent();
});

// Notification Tests

test('notification contains correct budget details', function (): void {
    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Budget',
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    $notification = new BudgetThresholdNotification($budget, 'warning', 80.0);

    $mailData = $notification->toMail($this->adminUser);
    $arrayData = $notification->toArray($this->adminUser);

    expect($arrayData['budget_id'])->toBe($budget->id);
    expect($arrayData['budget_name'])->toBe('Test Budget');
    expect($arrayData['alert_level'])->toBe('warning');
    expect($arrayData['utilization_percent'])->toBe(80.0);
});

test('notification uses correct email subject for each alert level', function (): void {
    $budget = Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'name' => 'Test Budget',
    ]);

    $warningNotification = new BudgetThresholdNotification($budget, 'warning', 75.0);
    $criticalNotification = new BudgetThresholdNotification($budget, 'critical', 90.0);
    $exceededNotification = new BudgetThresholdNotification($budget, 'exceeded', 110.0);

    $warningMail = $warningNotification->toMail($this->adminUser);
    $criticalMail = $criticalNotification->toMail($this->adminUser);
    $exceededMail = $exceededNotification->toMail($this->adminUser);

    expect($warningMail->subject)->toContain('Budget Alert');
    expect($criticalMail->subject)->toContain('Budget Critical');
    expect($exceededMail->subject)->toContain('Budget Exceeded');
});

// Command Output Tests

test('command returns success exit code', function (): void {
    $exitCode = Artisan::call('budgets:check-thresholds');
    expect($exitCode)->toBe(0);
});

test('command outputs correct message', function (): void {
    Notification::fake();

    Budget::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 800.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => now(),
    ]);

    Artisan::call('budgets:check-thresholds');

    $output = Artisan::output();
    expect($output)->toContain('Checking budget thresholds');
    expect($output)->toContain('Done!');
});
