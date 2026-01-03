<?php

use App\Enums\BranchRole;
use App\Enums\DonationType;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PledgeStatus;
use App\Livewire\Finance\FinanceReports;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();

    // Create admin user with access
    $this->adminUser = User::factory()->create();
    $this->adminUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin->value,
    ]);

    // Create volunteer user (no report access)
    $this->volunteerUser = User::factory()->create();
    $this->volunteerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer->value,
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// Authorization Tests

test('admin can access finance reports', function () {
    $this->actingAs($this->adminUser)
        ->get(route('finance.reports', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access finance reports', function () {
    $this->actingAs($this->volunteerUser)
        ->get(route('finance.reports', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('finance.reports', $this->branch))
        ->assertRedirect(route('login'));
});

// Summary Stats Tests

test('summary stats calculates total income correctly', function () {
    Donation::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $stats = $component->viewData('summaryStats');
    expect($stats['total_income'])->toBe(300.00);
    expect($stats['donation_count'])->toBe(3);
});

test('summary stats calculates total expenses correctly', function () {
    Expense::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'amount' => 50.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    // Pending expense should not be counted
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'status' => ExpenseStatus::Pending,
        'expense_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $stats = $component->viewData('summaryStats');
    expect($stats['total_expenses'])->toBe(100.00);
});

test('summary stats calculates net position correctly', function () {
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500.00,
        'donation_date' => now(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $stats = $component->viewData('summaryStats');
    expect($stats['net_position'])->toBe(300.00);
});

test('summary stats calculates pledge fulfillment rate correctly', function () {
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000.00,
        'amount_fulfilled' => 500.00,
        'status' => PledgeStatus::Active,
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500.00,
        'amount_fulfilled' => 500.00,
        'status' => PledgeStatus::Active,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $stats = $component->viewData('summaryStats');
    // Total pledged: 1500, Total fulfilled: 1000 = 66.7%
    expect($stats['pledge_fulfillment'])->toBe(66.7);
});

// Period Filtering Tests

test('period filtering works correctly', function () {
    // Old donation (outside 7-day period)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(10),
    ]);

    // Recent donation (within 7-day period)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200.00,
        'donation_date' => now()->subDays(3),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch])
        ->call('setPeriod', 7);

    $stats = $component->viewData('summaryStats');
    expect($stats['total_income'])->toBe(200.00);
});

// Report Type Switching Tests

test('can switch between report types', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch])
        ->assertSet('reportType', 'summary')
        ->call('setReportType', 'donations')
        ->assertSet('reportType', 'donations')
        ->call('setReportType', 'expenses')
        ->assertSet('reportType', 'expenses')
        ->call('setReportType', 'pledges')
        ->assertSet('reportType', 'pledges');
});

// Donations Report Tests

test('donations by type data is calculated correctly', function () {
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_type' => DonationType::Tithe,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_type' => DonationType::Offering,
        'amount' => 50.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $data = $component->viewData('donationsByTypeData');
    expect($data['labels'])->toContain('Tithe');
    expect($data['labels'])->toContain('Offering');
    expect(count($data['data']))->toBe(2);
});

test('top donors data returns correct members', function () {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 500.00,
        'is_anonymous' => false,
        'donation_date' => now(),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 200.00,
        'is_anonymous' => false,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $topDonors = $component->viewData('topDonorsData');
    expect($topDonors->count())->toBe(2);
    expect($topDonors->first()->member_id)->toBe($member1->id);
});

// Expenses Report Tests

test('expenses by category data is calculated correctly', function () {
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Utilities,
        'amount' => 150.00,
        'expense_date' => now(),
    ]);

    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'category' => ExpenseCategory::Salaries,
        'amount' => 300.00,
        'expense_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $data = $component->viewData('expensesByCategoryData');
    expect($data['labels'])->toContain('Utilities');
    expect($data['labels'])->toContain('Salaries');
});

// Pledges Report Tests

test('pledge fulfillment data is calculated correctly', function () {
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000.00,
        'amount_fulfilled' => 750.00,
        'status' => PledgeStatus::Active,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $data = $component->viewData('pledgeFulfillmentData');
    expect($data['total_pledged'])->toBe(1000.00);
    expect($data['total_fulfilled'])->toBe(750.00);
    expect($data['outstanding'])->toBe(250.00);
    expect($data['fulfillment_rate'])->toBe(75.0);
});

test('outstanding pledges data returns correct pledges', function () {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 500.00,
        'amount_fulfilled' => 200.00,
        'status' => PledgeStatus::Active,
    ]);

    // Completed pledge should not appear
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'amount_fulfilled' => 100.00,
        'status' => PledgeStatus::Completed,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $outstanding = $component->viewData('outstandingPledgesData');
    expect($outstanding->count())->toBe(1);
    expect($outstanding->first()->remainingAmount())->toBe(300.00);
});

// Empty State Tests

test('handles empty data gracefully', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceReports::class, ['branch' => $this->branch]);

    $stats = $component->viewData('summaryStats');
    expect($stats['total_income'])->toBe(0.0);
    expect($stats['total_expenses'])->toBe(0.0);
    expect($stats['net_position'])->toBe(0.0);
    expect($stats['pledge_fulfillment'])->toBe(0);
});
