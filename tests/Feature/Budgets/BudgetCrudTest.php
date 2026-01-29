<?php

use App\Enums\BranchRole;
use App\Enums\BudgetStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Expense;
use App\Models\User;
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

    // Create volunteer user
    $this->volunteerUser = User::factory()->create();
    $this->volunteerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer->value,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// Authorization Tests

test('admin can access budgets page', function (): void {
    $this->actingAs($this->adminUser)
        ->get(route('budgets.index', $this->branch))
        ->assertStatus(200);
});

test('manager can access budgets page', function (): void {
    $this->actingAs($this->managerUser)
        ->get(route('budgets.index', $this->branch))
        ->assertStatus(200);
});

test('volunteer can access budgets page', function (): void {
    $this->actingAs($this->volunteerUser)
        ->get(route('budgets.index', $this->branch))
        ->assertStatus(200);
});

test('unauthenticated user is redirected to login', function (): void {
    $this->get(route('budgets.index', $this->branch))
        ->assertRedirect(route('login'));
});

// Budget Model Tests

test('budget calculates actual spending from approved expenses', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'allocated_amount' => 1000.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    // Create approved expenses within budget period
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 200.00,
        'status' => ExpenseStatus::Approved,
        'expense_date' => '2025-06-15',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 150.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-07-20',
    ]);

    // Create pending expense (should not count)
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 100.00,
        'status' => ExpenseStatus::Pending,
        'expense_date' => '2025-08-01',
    ]);

    expect($budget->actual_spending)->toBe(350.00);
});

test('budget only counts expenses within date range', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Salaries,
        'allocated_amount' => 5000.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-06-30',
    ]);

    // Expense within range
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Salaries,
        'amount' => 1000.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-03-15',
    ]);

    // Expense outside range
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Salaries,
        'amount' => 500.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-09-01',
    ]);

    expect($budget->actual_spending)->toBe(1000.00);
});

test('budget only counts matching category expenses', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Maintenance,
        'allocated_amount' => 2000.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    // Matching category
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Maintenance,
        'amount' => 300.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-05-01',
    ]);

    // Different category
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Supplies,
        'amount' => 200.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-05-01',
    ]);

    expect($budget->actual_spending)->toBe(300.00);
});

test('budget calculates remaining amount correctly', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Events,
        'allocated_amount' => 1000.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Events,
        'amount' => 400.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-06-01',
    ]);

    expect($budget->remaining_amount)->toBe(600.00);
});

test('budget calculates utilization percentage correctly', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Transport,
        'allocated_amount' => 500.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Transport,
        'amount' => 250.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-04-01',
    ]);

    expect($budget->utilization_percentage)->toBe(50.0);
});

test('budget detects over-budget status', function (): void {
    $budget = Budget::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Supplies,
        'allocated_amount' => 200.00,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Supplies,
        'amount' => 250.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => '2025-03-01',
    ]);

    expect($budget->is_over_budget)->toBeTrue();
    expect($budget->utilization_percentage)->toBe(125.0);
});

// Policy Tests

test('admin can create budget', function (): void {
    expect($this->adminUser->can('create', [Budget::class, $this->branch]))->toBeTrue();
});

test('manager can create budget', function (): void {
    expect($this->managerUser->can('create', [Budget::class, $this->branch]))->toBeTrue();
});

test('staff cannot create budget', function (): void {
    expect($this->staffUser->can('create', [Budget::class, $this->branch]))->toBeFalse();
});

test('volunteer cannot create budget', function (): void {
    expect($this->volunteerUser->can('create', [Budget::class, $this->branch]))->toBeFalse();
});

test('admin can update budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->adminUser->can('update', $budget))->toBeTrue();
});

test('manager can update budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->managerUser->can('update', $budget))->toBeTrue();
});

test('staff cannot update budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->staffUser->can('update', $budget))->toBeFalse();
});

test('admin can delete budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->adminUser->can('delete', $budget))->toBeTrue();
});

test('manager cannot delete budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->managerUser->can('delete', $budget))->toBeFalse();
});

test('staff cannot delete budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);
    expect($this->staffUser->can('delete', $budget))->toBeFalse();
});

// Cross-Branch Security Tests

test('user cannot access budget from different branch', function (): void {
    $otherBranch = Branch::factory()->create();

    $this->actingAs($this->adminUser)
        ->get(route('budgets.index', $otherBranch))
        ->assertForbidden();
});

test('user cannot update budget from different branch', function (): void {
    $otherBranch = Branch::factory()->create();
    $budget = Budget::factory()->create(['branch_id' => $otherBranch->id]);

    expect($this->adminUser->can('update', $budget))->toBeFalse();
});

// Empty State Tests

test('handles empty budgets gracefully', function (): void {
    $this->actingAs($this->adminUser)
        ->get(route('budgets.index', $this->branch))
        ->assertStatus(200)
        ->assertSee('No budgets found');
});

// Factory Tests

test('budget factory creates valid budget', function (): void {
    $budget = Budget::factory()->create(['branch_id' => $this->branch->id]);

    expect($budget)->toBeInstanceOf(Budget::class);
    expect($budget->branch_id)->toBe($this->branch->id);
    expect($budget->allocated_amount)->toBeGreaterThan(0);
    expect($budget->status)->toBeInstanceOf(BudgetStatus::class);
    expect($budget->category)->toBeInstanceOf(ExpenseCategory::class);
});

test('budget factory active state works', function (): void {
    $budget = Budget::factory()->active()->create(['branch_id' => $this->branch->id]);
    expect($budget->status)->toBe(BudgetStatus::Active);
});

test('budget factory closed state works', function (): void {
    $budget = Budget::factory()->closed()->create(['branch_id' => $this->branch->id]);
    expect($budget->status)->toBe(BudgetStatus::Closed);
});

test('budget factory forYear state works', function (): void {
    $budget = Budget::factory()->forYear(2026)->create(['branch_id' => $this->branch->id]);
    expect($budget->fiscal_year)->toBe(2026);
    expect($budget->start_date->format('Y'))->toBe('2026');
    expect($budget->end_date->format('Y'))->toBe('2026');
});

test('budget factory forCategory state works', function (): void {
    $budget = Budget::factory()->forCategory(ExpenseCategory::Missions)->create(['branch_id' => $this->branch->id]);
    expect($budget->category)->toBe(ExpenseCategory::Missions);
});
