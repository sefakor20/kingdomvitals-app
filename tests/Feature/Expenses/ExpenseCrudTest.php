<?php

use App\Enums\BranchRole;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Livewire\Expenses\ExpenseIndex;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Expense;
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

test('authenticated user with branch access can view expenses page', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/expenses")
        ->assertOk()
        ->assertSeeLivewire(ExpenseIndex::class);
});

test('user without branch access cannot view expenses page', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/expenses")
        ->assertForbidden();
});

test('unauthenticated user cannot view expenses page', function (): void {
    $this->get("/branches/{$this->branch->id}/expenses")
        ->assertRedirect('/login');
});

// ============================================
// VIEW EXPENSES AUTHORIZATION TESTS
// ============================================

test('admin can view expenses list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee($expense->description);
});

test('volunteer can view expenses list', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee($expense->description);
});

// ============================================
// CREATE EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can create an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('description', 'Electricity bill payment')
        ->set('amount', '500.00')
        ->set('category', 'utilities')
        ->set('payment_method', 'bank_transfer')
        ->set('expense_date', now()->format('Y-m-d'))
        ->set('vendor_name', 'ECG')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('expense-created');

    $expense = Expense::where('description', 'Electricity bill payment')->first();
    expect($expense)->not->toBeNull();
    expect($expense->status)->toBe(ExpenseStatus::Pending);
});

test('manager can create an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Office supplies')
        ->set('amount', '150.00')
        ->set('category', 'supplies')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Expense::where('description', 'Office supplies')->exists())->toBeTrue();
});

test('staff can create an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Cleaning materials')
        ->set('amount', '75.00')
        ->set('category', 'supplies')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Expense::where('description', 'Cleaning materials')->exists())->toBeTrue();
});

test('volunteer cannot create an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// UPDATE EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can update an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $expense)
        ->assertSet('showEditModal', true)
        ->set('description', 'Updated expense description')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('expense-updated');

    expect($expense->fresh()->description)->toBe('Updated expense description');
});

test('volunteer cannot update an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $expense)
        ->assertForbidden();
});

// ============================================
// DELETE EXPENSE AUTHORIZATION TESTS
// ============================================

test('admin can delete an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);
    $expenseId = $expense->id;

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $expense)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('expense-deleted');

    expect(Expense::find($expenseId))->toBeNull();
});

test('manager can delete an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);
    $expenseId = $expense->id;

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $expense)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Expense::find($expenseId))->toBeNull();
});

test('staff cannot delete an expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $expense)
        ->assertForbidden();
});

// ============================================
// APPROVAL WORKFLOW TESTS
// ============================================

test('admin can approve pending expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmApprove', $expense)
        ->assertSet('showApproveModal', true)
        ->call('approve')
        ->assertSet('showApproveModal', false)
        ->assertDispatched('expense-approved');

    $expense->refresh();
    expect($expense->status)->toBe(ExpenseStatus::Approved);
    expect($expense->approved_at)->not->toBeNull();
});

test('manager can approve pending expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmApprove', $expense)
        ->call('approve')
        ->assertHasNoErrors();

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Approved);
});

test('staff cannot approve expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmApprove', $expense)
        ->assertForbidden();
});

test('admin can reject pending expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmReject', $expense)
        ->assertSet('showRejectModal', true)
        ->call('reject')
        ->assertSet('showRejectModal', false)
        ->assertDispatched('expense-rejected');

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Rejected);
});

test('manager can reject pending expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmReject', $expense)
        ->call('reject')
        ->assertHasNoErrors();

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Rejected);
});

test('staff cannot reject expense', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmReject', $expense)
        ->assertForbidden();
});

test('can mark approved expense as paid', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->approved()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('markAsPaid', $expense)
        ->assertDispatched('expense-paid');

    expect($expense->fresh()->status)->toBe(ExpenseStatus::Paid);
});

test('cannot mark pending expense as paid', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('markAsPaid', $expense);

    // Status should remain pending
    expect($expense->fresh()->status)->toBe(ExpenseStatus::Pending);
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search expenses by description', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Searchable expense item',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'description' => 'Hidden expense item',
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->set('search', 'Searchable')
        ->assertSee('Searchable expense item')
        ->assertDontSee('Hidden expense item');
});

test('can filter expenses by category', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Expense::factory()->utilities()->create([
        'branch_id' => $this->branch->id,
    ]);

    Expense::factory()->maintenance()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->set('categoryFilter', 'utilities');

    expect($component->instance()->expenses->count())->toBe(1);
    expect($component->instance()->expenses->first()->category)->toBe(ExpenseCategory::Utilities);
});

test('can filter expenses by status', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Expense::factory()->pending()->create([
        'branch_id' => $this->branch->id,
    ]);

    Expense::factory()->approved()->create([
        'branch_id' => $this->branch->id,
    ]);

    Expense::factory()->rejected()->create([
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->set('statusFilter', 'pending');

    expect($component->instance()->expenses->count())->toBe(1);
    expect($component->instance()->expenses->first()->status)->toBe(ExpenseStatus::Pending);
});

test('can filter expenses by date range', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'expense_date' => now()->subDays(5),
        'description' => 'Recent expense',
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'expense_date' => now()->subDays(30),
        'description' => 'Old expense',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'));

    expect($component->instance()->expenses->count())->toBe(1);
    expect($component->instance()->expenses->first()->description)->toBe('Recent expense');
});

// ============================================
// STATS TESTS
// ============================================

test('expense stats are calculated correctly', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Expense::factory()->pending()->thisMonth()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500,
    ]);

    Expense::factory()->approved()->thisMonth()->create([
        'branch_id' => $this->branch->id,
        'amount' => 300,
    ]);

    Expense::factory()->pending()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200,
        'expense_date' => now()->subMonths(2),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExpenseIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->expenseStats;

    expect($stats['total'])->toBe(1000.0);
    expect($stats['count'])->toBe(3);
    expect($stats['pending'])->toBe(2);
    expect($stats['thisMonth'])->toBe(800.0);
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

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', '')
        ->set('amount', '100')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
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

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '')
        ->set('category', 'utilities')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
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

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', '')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['category']);
});

test('category must be valid', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('description', 'Test expense')
        ->set('amount', '100')
        ->set('category', 'invalid_category')
        ->set('payment_method', 'cash')
        ->set('expense_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['category']);
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

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('description', 'Test')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('description', '');
});

test('cancel approve modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmApprove', $expense)
        ->assertSet('showApproveModal', true)
        ->call('cancelApprove')
        ->assertSet('showApproveModal', false)
        ->assertSet('approvingExpense', null);
});

test('cancel reject modal closes modal', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmReject', $expense)
        ->assertSet('showRejectModal', true)
        ->call('cancelReject')
        ->assertSet('showRejectModal', false)
        ->assertSet('rejectingExpense', null);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no expenses exist', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee('No expenses found');
});

test('create button is visible for users with create permission', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->assertSee('Add Expense');
});

test('create button is hidden for volunteers', function (): void {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ExpenseIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->canCreate)->toBeFalse();
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update expense from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('edit', $expense)
        ->assertForbidden();
});

test('user cannot approve expense from different branch', function (): void {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $expense = Expense::factory()->pending()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(ExpenseIndex::class, ['branch' => $this->branch])
        ->call('confirmApprove', $expense)
        ->assertForbidden();
});
