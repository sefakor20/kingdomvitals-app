<?php

use App\Enums\BranchRole;
use App\Enums\DonationType;
use App\Enums\ExpenseStatus;
use App\Enums\MembershipStatus;
use App\Enums\PledgeStatus;
use App\Livewire\Finance\FinanceDashboard;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\User;
use Livewire\Livewire;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

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

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// Authorization Tests

test('admin can access finance dashboard', function (): void {
    $this->actingAs($this->adminUser)
        ->get(route('finance.dashboard', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access finance dashboard', function (): void {
    $this->actingAs($this->volunteerUser)
        ->get(route('finance.dashboard', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user is redirected to login', function (): void {
    $this->get(route('finance.dashboard', $this->branch))
        ->assertRedirect(route('login'));
});

// Monthly Stats Tests

test('monthly stats calculates income correctly', function (): void {
    Donation::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    // Last month donation should not be counted
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500.00,
        'donation_date' => now()->subMonth(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('monthlyStats');
    expect($stats['income'])->toBe(300.0);
    expect($stats['income_count'])->toBe(3);
});

test('monthly stats calculates expenses correctly', function (): void {
    Expense::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'amount' => 50.00,
        'status' => ExpenseStatus::Paid,
        'expense_date' => now(),
    ]);

    // Pending expense should not be counted in expenses sum
    Expense::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'status' => ExpenseStatus::Pending,
        'expense_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('monthlyStats');
    expect($stats['expenses'])->toBe(100.0);
    expect($stats['expenses_count'])->toBe(3); // Count includes pending
});

test('monthly stats calculates net position correctly', function (): void {
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
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('monthlyStats');
    expect($stats['net_position'])->toBe(300.0);
});

// Year-to-Date Stats Tests

test('ytd stats calculates income correctly', function (): void {
    // Create donation at the start of this year (yesterday to ensure it's in the past)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000.00,
        'donation_date' => now()->subDays(1),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500.00,
        'donation_date' => now()->subDays(2),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('yearToDateStats');
    expect($stats['income'])->toBe(1500.0);
});

test('ytd stats calculates income growth percentage', function (): void {
    // Last year same period (use yesterday's date minus 1 year)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000.00,
        'donation_date' => now()->subDays(1)->subYear(),
    ]);

    // This year (yesterday)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1500.00,
        'donation_date' => now()->subDays(1),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('yearToDateStats');
    expect($stats['income_growth_percent'])->toBe(50.0); // 50% increase
});

// Outstanding Pledges Tests

test('outstanding pledges total is calculated correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 1000.00,
        'amount_fulfilled' => 300.00,
        'status' => PledgeStatus::Active,
    ]);

    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 500.00,
        'amount_fulfilled' => 200.00,
        'status' => PledgeStatus::Active,
    ]);

    // Completed pledge should not count
    Pledge::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 200.00,
        'amount_fulfilled' => 200.00,
        'status' => PledgeStatus::Completed,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $total = $component->get('outstandingPledgesTotal');
    // (1000 - 300) + (500 - 200) = 700 + 300 = 1000
    expect($total)->toBe(1000.0);
});

// Member Giving Stats Tests

test('member giving stats calculates average donation correctly', function (): void {
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('memberGivingStats');
    expect($stats['average_donation'])->toBe(150.0);
});

test('member giving stats calculates unique donors correctly', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Member 1 donates twice
    Donation::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    // Member 2 donates once
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 50.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('memberGivingStats');
    expect($stats['unique_donors'])->toBe(2);
});

test('member giving stats calculates giving percentage correctly', function (): void {
    // Create 10 active members
    Member::factory()->count(10)->create([
        'primary_branch_id' => $this->branch->id,
        'status' => MembershipStatus::Active,
    ]);

    // 3 members donate
    $donors = Member::where('primary_branch_id', $this->branch->id)->take(3)->get();
    foreach ($donors as $donor) {
        Donation::factory()->create([
            'branch_id' => $this->branch->id,
            'member_id' => $donor->id,
            'amount' => 100.00,
            'donation_date' => now(),
        ]);
    }

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('memberGivingStats');
    expect($stats['active_members'])->toBe(10);
    expect($stats['unique_donors'])->toBe(3);
    expect($stats['giving_percentage'])->toBe(30.0);
});

test('member giving stats calculates first time donors correctly', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member3 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Member 1 donated last month and this month (not first-time)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 100.00,
        'donation_date' => now()->subMonth(),
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    // Member 2 only donated this month (first-time)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 50.00,
        'donation_date' => now(),
    ]);

    // Member 3 only donated this month (first-time)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member3->id,
        'amount' => 75.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('memberGivingStats');
    expect($stats['first_time_donors'])->toBe(2);
});

// Chart Data Tests

test('monthly income chart data returns correct structure', function (): void {
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $chartData = $component->get('monthlyIncomeChartData');
    expect($chartData)->toHaveKeys(['labels', 'current_year', 'previous_year']);
    expect(count($chartData['labels']))->toBe(12);
    expect(count($chartData['current_year']))->toBe(12);
    expect(count($chartData['previous_year']))->toBe(12);
});

test('donation types chart data returns correct structure', function (): void {
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
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $chartData = $component->get('donationTypesChartData');
    expect($chartData)->toHaveKeys(['labels', 'data', 'colors']);
    expect($chartData['labels'])->toContain('Tithe');
    expect($chartData['labels'])->toContain('Offering');
});

test('income vs expenses chart data returns correct structure', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $chartData = $component->get('incomeVsExpensesChartData');
    expect($chartData)->toHaveKeys(['labels', 'income', 'expenses']);
    expect(count($chartData['labels']))->toBe(6);
    expect(count($chartData['income']))->toBe(6);
    expect(count($chartData['expenses']))->toBe(6);
});

test('donation growth chart data returns correct structure', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $chartData = $component->get('donationGrowthChartData');
    expect($chartData)->toHaveKeys(['labels', 'data']);
    expect(count($chartData['labels']))->toBe(12);
    expect(count($chartData['data']))->toBe(12);
});

test('top donors tier data returns correct structure', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Donation::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'is_anonymous' => false,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $tierData = $component->get('topDonorsTierData');
    expect($tierData)->toHaveKeys(['tiers', 'amounts', 'counts']);
    expect(count($tierData['tiers']))->toBe(4);
    expect(count($tierData['amounts']))->toBe(4);
    expect(count($tierData['counts']))->toBe(4);
});

// Branch Scoping Tests

test('data is scoped to current branch', function (): void {
    $otherBranch = Branch::factory()->create();

    // Donation in current branch
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100.00,
        'donation_date' => now(),
    ]);

    // Donation in other branch (should not be included)
    Donation::factory()->create([
        'branch_id' => $otherBranch->id,
        'amount' => 500.00,
        'donation_date' => now(),
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $stats = $component->get('monthlyStats');
    expect($stats['income'])->toBe(100.0);
    expect($stats['income_count'])->toBe(1);
});

// Empty State Tests

test('handles empty data gracefully', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch]);

    $monthlyStats = $component->get('monthlyStats');
    expect($monthlyStats['income'])->toBe(0.0);
    expect($monthlyStats['expenses'])->toBe(0.0);
    expect($monthlyStats['net_position'])->toBe(0.0);

    $ytdStats = $component->get('yearToDateStats');
    expect($ytdStats['income'])->toBe(0.0);

    $memberStats = $component->get('memberGivingStats');
    expect($memberStats['average_donation'])->toBe(0.0);
    expect($memberStats['unique_donors'])->toBe(0);

    $pledgesTotal = $component->get('outstandingPledgesTotal');
    expect($pledgesTotal)->toBe(0.0);
});

// Component Renders Tests

test('component renders successfully', function (): void {
    Livewire::actingAs($this->adminUser)
        ->test(FinanceDashboard::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Financial Dashboard')
        ->assertSee('Current Month')
        ->assertSee('Year-over-Year Comparison')
        ->assertSee('Member Giving Statistics');
});
