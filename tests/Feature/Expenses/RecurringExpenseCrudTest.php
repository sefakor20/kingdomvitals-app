<?php

use App\Enums\BranchRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PledgeFrequency;
use App\Enums\RecurringExpenseStatus;
use App\Livewire\Expenses\RecurringExpenseIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Expense;
use App\Models\Tenant\RecurringExpense;
use App\Models\Tenant\UserBranchAccess;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view recurring expenses page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/expenses/recurring")
        ->assertOk()
        ->assertSeeLivewire(RecurringExpenseIndex::class);
});

test('user without branch access cannot view recurring expenses page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/expenses/recurring")
        ->assertForbidden();
});

test('unauthenticated user cannot view recurring expenses page', function (): void {
    $this->get("/branches/{$this->branch->id}/expenses/recurring")
        ->assertRedirect('/login');
});

// ============================================
// VIEW RECURRING EXPENSES AUTHORIZATION TESTS
// ============================================

test('admin can view recurring expenses list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee($recurringExpense->description);
});

test('volunteer can view recurring expenses list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee($recurringExpense->description);
});

// ============================================
// CREATE RECURRING EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can create a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('description', 'Monthly Electricity Bill')
        ->set('amount', '500.00')
        ->set('category', 'utilities')
        ->set('payment_method', 'bank_transfer')
        ->set('vendor_name', 'ECG')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->set('day_of_month', 15)
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('recurring-expense-created');

    $recurringExpense = RecurringExpense::where('description', 'Monthly Electricity Bill')->first();
    expect($recurringExpense)->not->toBeNull();
    expect($recurringExpense->status)->toBe(RecurringExpenseStatus::Active);
    expect($recurringExpense->frequency)->toBe(PledgeFrequency::Monthly);
});

test('manager can create a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Weekly Cleaning Service')
        ->set('amount', '150.00')
        ->set('category', 'maintenance')
        ->set('payment_method', 'cash')
        ->set('frequency', 'weekly')
        ->set('start_date', now()->format('Y-m-d'))
        ->set('day_of_week', 1)
        ->call('store')
        ->assertHasNoErrors();

    expect(RecurringExpense::where('description', 'Weekly Cleaning Service')->exists())->toBeTrue();
});

test('staff cannot create a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

test('volunteer cannot create a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE RECURRING EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can update a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $recurringExpense)
        ->assertSet('showEditModal', true)
        ->set('description', 'Updated expense description')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('recurring-expense-updated');

    expect($recurringExpense->fresh()->description)->toBe('Updated expense description');
});

test('manager can update a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $recurringExpense)
        ->set('amount', '999.99')
        ->call('update')
        ->assertHasNoErrors();

    expect($recurringExpense->fresh()->amount)->toBe('999.99');
});

test('staff cannot update a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $recurringExpense)
        ->assertForbidden();
});

// ============================================
// DELETE RECURRING EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can delete a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);
    $recurringExpenseId = $recurringExpense->id;

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $recurringExpense)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('recurring-expense-deleted');

    expect(RecurringExpense::find($recurringExpenseId))->toBeNull();
});

test('manager can delete a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);
    $recurringExpenseId = $recurringExpense->id;

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $recurringExpense)
        ->call('delete')
        ->assertHasNoErrors();

    expect(RecurringExpense::find($recurringExpenseId))->toBeNull();
});

test('staff cannot delete a recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $recurringExpense)
        ->assertForbidden();
});

// ============================================
// TOGGLE STATUS TESTS
// ============================================

test('admin can pause an active recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('toggleStatus', $recurringExpense)
        ->assertDispatched('recurring-expense-status-changed');

    expect($recurringExpense->fresh()->status)->toBe(RecurringExpenseStatus::Paused);
});

test('admin can resume a paused recurring expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->paused()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('toggleStatus', $recurringExpense)
        ->assertDispatched('recurring-expense-status-changed');

    expect($recurringExpense->fresh()->status)->toBe(RecurringExpenseStatus::Active);
});

test('manager can toggle recurring expense status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('toggleStatus', $recurringExpense)
        ->assertHasNoErrors();

    expect($recurringExpense->fresh()->status)->toBe(RecurringExpenseStatus::Paused);
});

test('staff cannot toggle recurring expense status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('toggleStatus', $recurringExpense)
        ->assertForbidden();
});

// ============================================
// GENERATE NOW TESTS
// ============================================

test('admin can manually generate expense from recurring template', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Monthly Rent',
        'amount' => 1500.00,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmGenerate', $recurringExpense)
        ->assertSet('showGenerateModal', true)
        ->call('generateNow')
        ->assertSet('showGenerateModal', false)
        ->assertDispatched('recurring-expense-generated');

    $expense = Expense::where('recurring_expense_id', $recurringExpense->id)->first();
    expect($expense)->not->toBeNull();
    expect($expense->description)->toBe('Monthly Rent');
    expect($expense->amount)->toBe('1500.00');
    expect($expense->status)->toBe(ExpenseStatus::Pending);

    expect($recurringExpense->fresh()->total_generated_count)->toBe(1);
});

test('manager can manually generate expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $recurringExpense = RecurringExpense::factory()->dueToday()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmGenerate', $recurringExpense)
        ->call('generateNow')
        ->assertHasNoErrors();

    expect(Expense::where('recurring_expense_id', $recurringExpense->id)->exists())->toBeTrue();
});

test('staff cannot manually generate expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $recurringExpense = RecurringExpense::factory()->dueToday()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmGenerate', $recurringExpense)
        ->assertForbidden();
});

// ============================================
// MODEL GENERATION LOGIC TESTS
// ============================================

test('recurring expense generates expense with correct data', function (): void {
    $recurringExpense = RecurringExpense::factory()->dueToday()->utilities()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Electricity Bill',
        'amount' => 250.00,
        'vendor_name' => 'ECG',
        'notes' => 'Monthly payment',
    ]);

    $expense = $recurringExpense->generateExpense();

    expect($expense)->not->toBeNull();
    expect($expense->description)->toBe('Electricity Bill');
    expect($expense->amount)->toBe('250.00');
    expect($expense->vendor_name)->toBe('ECG');
    expect($expense->category)->toBe(ExpenseCategory::Utilities);
    expect($expense->status)->toBe(ExpenseStatus::Pending);
    expect($expense->recurring_expense_id)->toBe($recurringExpense->id);
    expect($expense->notes)->toContain('Auto-generated');
});

test('paused recurring expense does not generate', function (): void {
    $recurringExpense = RecurringExpense::factory()->paused()->create([
        'branch_id' => $this->branch->id,
        'next_generation_date' => now()->toDateString(),
    ]);

    $expense = $recurringExpense->generateExpense();

    expect($expense)->toBeNull();
});

test('recurring expense with future date does not generate', function (): void {
    $recurringExpense = RecurringExpense::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'next_generation_date' => now()->addWeek()->toDateString(),
    ]);

    $expense = $recurringExpense->generateExpense();

    expect($expense)->toBeNull();
});

test('recurring expense past end date does not generate', function (): void {
    $recurringExpense = RecurringExpense::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'next_generation_date' => now()->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    $expense = $recurringExpense->generateExpense();

    expect($expense)->toBeNull();
});

test('recurring expense updates next generation date after generating', function (): void {
    $recurringExpense = RecurringExpense::factory()->monthly()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'day_of_month' => 15,
    ]);

    $originalNextDate = $recurringExpense->next_generation_date;
    $recurringExpense->generateExpense();

    $recurringExpense->refresh();
    expect($recurringExpense->last_generated_date->isToday())->toBeTrue();
    expect($recurringExpense->next_generation_date)->not->toBeNull();
    expect($recurringExpense->next_generation_date->gt($originalNextDate))->toBeTrue();
});

test('recurring expense increments generation count after generating', function (): void {
    $recurringExpense = RecurringExpense::factory()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'total_generated_count' => 5,
    ]);

    $recurringExpense->generateExpense();

    expect($recurringExpense->fresh()->total_generated_count)->toBe(6);
});

test('recurring expense marks as completed when no more dates', function (): void {
    $recurringExpense = RecurringExpense::factory()->monthly()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'end_date' => now()->addDays(15)->toDateString(),
    ]);

    $recurringExpense->generateExpense();

    expect($recurringExpense->fresh()->status)->toBe(RecurringExpenseStatus::Completed);
});

// ============================================
// NEXT DATE CALCULATION TESTS
// ============================================

test('weekly recurring expense calculates next date correctly', function (): void {
    $recurringExpense = RecurringExpense::factory()->weekly()->create([
        'branch_id' => $this->branch->id,
        'start_date' => now()->toDateString(),
    ]);

    $nextDate = $recurringExpense->calculateNextGenerationDate(now());

    expect($nextDate)->not->toBeNull();
    expect($nextDate->diffInDays(now()))->toBeLessThanOrEqual(7);
});

test('monthly recurring expense calculates next date correctly', function (): void {
    $recurringExpense = RecurringExpense::factory()->monthly()->create([
        'branch_id' => $this->branch->id,
        'start_date' => now()->toDateString(),
        'day_of_month' => 15,
    ]);

    $nextDate = $recurringExpense->calculateNextGenerationDate(now());

    expect($nextDate)->not->toBeNull();
    expect($nextDate->day)->toBe(15);
});

test('quarterly recurring expense calculates next date correctly', function (): void {
    $recurringExpense = RecurringExpense::factory()->quarterly()->create([
        'branch_id' => $this->branch->id,
        'start_date' => now()->toDateString(),
    ]);

    $nextDate = $recurringExpense->calculateNextGenerationDate(now());

    expect($nextDate)->not->toBeNull();
    // Quarterly means ~3 months from now (depending on day_of_month, could be 85-105 days)
    $daysDiff = now()->diffInDays($nextDate, false);
    expect($daysDiff)->toBeGreaterThanOrEqual(80);
    expect($daysDiff)->toBeLessThanOrEqual(110);
});

test('yearly recurring expense calculates next date correctly', function (): void {
    $recurringExpense = RecurringExpense::factory()->yearly()->create([
        'branch_id' => $this->branch->id,
        'start_date' => now()->toDateString(),
    ]);

    $nextDate = $recurringExpense->calculateNextGenerationDate(now());

    expect($nextDate)->not->toBeNull();
    expect($nextDate->year)->toBe(now()->year + 1);
});

test('next date returns null when past end date', function (): void {
    $recurringExpense = RecurringExpense::factory()->monthly()->create([
        'branch_id' => $this->branch->id,
        'start_date' => now()->subMonths(6)->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    $nextDate = $recurringExpense->calculateNextGenerationDate(now());

    expect($nextDate)->toBeNull();
});

// ============================================
// MONTHLY PROJECTION TESTS
// ============================================

test('weekly expense has correct monthly projection', function (): void {
    $recurringExpense = RecurringExpense::factory()->weekly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100,
    ]);

    expect($recurringExpense->monthly_projection)->toBeGreaterThan(400);
    expect($recurringExpense->monthly_projection)->toBeLessThan(500);
});

test('monthly expense has correct monthly projection', function (): void {
    $recurringExpense = RecurringExpense::factory()->monthly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500,
    ]);

    expect($recurringExpense->monthly_projection)->toBe(500.0);
});

test('quarterly expense has correct monthly projection', function (): void {
    $recurringExpense = RecurringExpense::factory()->quarterly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 300,
    ]);

    expect($recurringExpense->monthly_projection)->toBe(100.0);
});

test('yearly expense has correct monthly projection', function (): void {
    $recurringExpense = RecurringExpense::factory()->yearly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1200,
    ]);

    expect($recurringExpense->monthly_projection)->toBe(100.0);
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search recurring expenses by description', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    RecurringExpense::factory()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Searchable recurring expense',
    ]);

    RecurringExpense::factory()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Hidden recurring expense',
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->set('search', 'Searchable')
        ->assertSee('Searchable recurring expense')
        ->assertDontSee('Hidden recurring expense');
});

test('can filter recurring expenses by category', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    RecurringExpense::factory()->utilities()->create(['branch_id' => $this->branch->id]);
    RecurringExpense::factory()->salaries()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->set('categoryFilter', 'utilities');

    expect($component->instance()->recurringExpenses->count())->toBe(1);
    expect($component->instance()->recurringExpenses->first()->category)->toBe(ExpenseCategory::Utilities);
});

test('can filter recurring expenses by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    RecurringExpense::factory()->active()->create(['branch_id' => $this->branch->id]);
    RecurringExpense::factory()->paused()->create(['branch_id' => $this->branch->id]);
    RecurringExpense::factory()->completed()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    $component = Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'active');

    expect($component->instance()->recurringExpenses->count())->toBe(1);
    expect($component->instance()->recurringExpenses->first()->status)->toBe(RecurringExpenseStatus::Active);
});

test('can clear filters', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    RecurringExpense::factory()->utilities()->create(['branch_id' => $this->branch->id]);
    RecurringExpense::factory()->salaries()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->set('search', 'test')
        ->set('categoryFilter', 'utilities')
        ->set('statusFilter', 'active')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('categoryFilter', '')
        ->assertSet('statusFilter', '');
});

// ============================================
// STATS TESTS
// ============================================

test('stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    RecurringExpense::factory()->active()->monthly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500,
    ]);

    RecurringExpense::factory()->active()->monthly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 300,
    ]);

    RecurringExpense::factory()->paused()->monthly()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->stats;

    expect($stats['total'])->toBe(3);
    expect($stats['active'])->toBe(2);
    expect($stats['paused'])->toBe(1);
    expect($stats['monthly_projection'])->toBe(800.0);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('description is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', '')
        ->set('amount', '100')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['description']);
});

test('amount is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['amount']);
});

test('category is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', '')
        ->set('payment_method', 'cash')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['category']);
});

test('frequency is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('frequency', '')
        ->set('start_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['frequency']);
});

test('start date is required', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('frequency', 'monthly')
        ->set('start_date', '')
        ->call('store')
        ->assertHasErrors(['start_date']);
});

test('end date must be after start date', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('frequency', 'monthly')
        ->set('start_date', now()->format('Y-m-d'))
        ->set('end_date', now()->subMonth()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['end_date']);
});

// ============================================
// MODAL CANCEL TESTS
// ============================================

test('cancel create modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('description', 'Test')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('description', '');
});

test('cancel edit modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $recurringExpense)
        ->assertSet('showEditModal', true)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingRecurringExpense', null);
});

test('cancel delete modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $recurringExpense)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingRecurringExpense', null);
});

test('cancel generate modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->dueToday()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmGenerate', $recurringExpense)
        ->assertSet('showGenerateModal', true)
        ->call('cancelGenerate')
        ->assertSet('showGenerateModal', false)
        ->assertSet('generatingRecurringExpense', null);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no recurring expenses exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee('No recurring expenses found');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Recurring');
});

test('create button is hidden for staff', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->canCreate)->toBeFalse();
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update recurring expense from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $recurringExpense)
        ->assertForbidden();
});

test('user cannot toggle status of recurring expense from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $recurringExpense = RecurringExpense::factory()->active()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(RecurringExpenseIndex::class, ['branch' => $this->branch])
        ->call('toggleStatus', $recurringExpense)
        ->assertForbidden();
});

// ============================================
// EXPENSE RELATIONSHIP TESTS
// ============================================

test('expense knows if it is from recurring expense', function (): void {
    $recurringExpense = RecurringExpense::factory()->dueToday()->create(['branch_id' => $this->branch->id]);
    $generatedExpense = $recurringExpense->generateExpense();

    $regularExpense = Expense::factory()->create(['branch_id' => $this->branch->id]);

    expect($generatedExpense->isFromRecurringExpense())->toBeTrue();
    expect($regularExpense->isFromRecurringExpense())->toBeFalse();
});

test('recurring expense can access its generated expenses', function (): void {
    $recurringExpense = RecurringExpense::factory()->dueToday()->monthly()->create([
        'branch_id' => $this->branch->id,
    ]);

    $recurringExpense->generateExpense();
    $recurringExpense->update(['next_generation_date' => now()->toDateString()]);
    $recurringExpense->generateExpense();

    expect($recurringExpense->generatedExpenses->count())->toBe(2);
});

// ============================================
// COMMAND TESTS
// ============================================

test('generate recurring expenses command works', function (): void {
    RecurringExpense::factory()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Monthly Rent',
    ]);

    RecurringExpense::factory()->active()->create([
        'branch_id' => $this->branch->id,
        'next_generation_date' => now()->addWeek()->toDateString(),
    ]);

    $this->artisan('expenses:generate-recurring')
        ->assertSuccessful()
        ->expectsOutputToContain('Generated 1 expense(s)');

    // Re-initialize tenancy to check results (command ends tenancy context)
    tenancy()->initialize($this->tenant);
    expect(Expense::where('description', 'Monthly Rent')->exists())->toBeTrue();
});

test('generate recurring expenses command dry run does not create expenses', function (): void {
    RecurringExpense::factory()->dueToday()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Monthly Rent',
    ]);

    $this->artisan('expenses:generate-recurring', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('DRY RUN MODE');

    // Re-initialize tenancy to check results (command ends tenancy context)
    tenancy()->initialize($this->tenant);
    expect(Expense::where('description', 'Monthly Rent')->exists())->toBeFalse();
});
