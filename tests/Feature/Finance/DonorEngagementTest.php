<?php

use App\Enums\BranchRole;
use App\Livewire\Finance\DonorEngagement;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
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

test('admin can access donor engagement', function (): void {
    $this->actingAs($this->adminUser)
        ->get(route('finance.donor-engagement', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access donor engagement', function (): void {
    $this->actingAs($this->volunteerUser)
        ->get(route('finance.donor-engagement', $this->branch))
        ->assertForbidden();
});

test('unauthenticated user is redirected to login', function (): void {
    $this->get(route('finance.donor-engagement', $this->branch))
        ->assertRedirect(route('login'));
});

// Retention Metrics Tests

test('retention metrics calculates returning donors correctly', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Use default 90-day period
    // Current period: 0-90 days ago
    // Previous period: 91-181 days ago

    // Member 1 donated in both periods (returning)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'donation_date' => today()->subDays(10)->toDateString(),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'donation_date' => today()->subDays(100)->toDateString(),
        'is_anonymous' => false,
    ]);

    // Member 2 only in current period (new)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'donation_date' => today()->subDays(10)->toDateString(),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    expect($metrics['returning_donors'])->toBe(1);
    expect($metrics['new_donors'])->toBe(1);
});

test('retention metrics calculates lapsed donors correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Member only donated in previous period (lapsed)
    // With 90-day period: previous period is 91-181 days ago
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    expect($metrics['lapsed_donors'])->toBe(1);
});

test('retention metrics calculates reactivated donors correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Member donated long ago, skipped previous period, came back in current period (reactivated)
    // With 90-day period: current=0-90, previous=91-181, before=182+
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(200),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    expect($metrics['reactivated_donors'])->toBe(1);
});

test('retention metrics calculates retention rate correctly', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // With 90-day period: current=0-90, previous=91-181

    // Member 1 donated in both periods (returning)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    // Member 2 only in previous period (lapsed)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    // 1 returning out of 2 previous = 50% retention
    expect($metrics['retention_rate'])->toBe(50.0);
    expect($metrics['churn_rate'])->toBe(50.0);
});

// Giving Trends Tests

test('giving trends identifies increasing donors correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // With 90-day period: current=0-90, previous=91-181
    // Previous period: 100
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    // Current period: 200 (>25% increase)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 200.00,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $trends = $component->get('givingTrends');
    expect($trends['increasing_count'])->toBe(1);
});

test('giving trends identifies declining donors correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // With 90-day period: current=0-90, previous=91-181
    // Previous period: 200
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 200.00,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    // Current period: 100 (>25% decrease)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $trends = $component->get('givingTrends');
    expect($trends['declining_count'])->toBe(1);
});

test('giving trends identifies consistent donors correctly', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // With 90-day period: current=0-90, previous=91-181
    // Previous period: 100
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);

    // Current period: 110 (within 25% range)
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 110.00,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $trends = $component->get('givingTrends');
    expect($trends['consistent_count'])->toBe(1);
});

// Donor Segments Tests

test('donor segments calculates major donors correctly', function (): void {
    // Create 10 donors with varying amounts (YTD donations)
    // Donations must be within the current year for segments
    for ($i = 1; $i <= 10; $i++) {
        $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
        Donation::factory()->create([
            'branch_id' => $this->branch->id,
            'member_id' => $member->id,
            'amount' => $i * 100.00,
            'donation_date' => now()->startOfYear()->addDays($i), // Within current year
            'is_anonymous' => false,
        ]);
    }

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $segments = $component->get('donorSegments');
    // Top 10% of 10 donors = 1 major donor
    expect($segments['major']['count'])->toBe(1);
    expect($segments['major']['total'])->toBe(1000.0); // Highest donor gave 1000
});

test('donor segments chart data returns correct structure', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $segments = $component->get('donorSegments');
    expect($segments['chart_data'])->toHaveKeys(['labels', 'data', 'colors']);
    expect(count($segments['chart_data']['labels']))->toBe(3);
    expect(count($segments['chart_data']['data']))->toBe(3);
    expect(count($segments['chart_data']['colors']))->toBe(3);
});

// Donors List Tests

test('donors list returns paginated results', function (): void {
    // Create 15 donors
    for ($i = 1; $i <= 15; $i++) {
        $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
        Donation::factory()->create([
            'branch_id' => $this->branch->id,
            'member_id' => $member->id,
            'amount' => $i * 10.00,
            'donation_date' => now()->subDays(30),
            'is_anonymous' => false,
        ]);
    }

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $list = $component->get('donorsList');
    expect($list->count())->toBe(10); // 10 per page
    expect($list->total())->toBe(15);
});

test('donors list can be searched by name', function (): void {
    $member1 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'last_name' => 'Smith',
    ]);
    $member2 = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch])
        ->set('donorSearch', 'John');

    $list = $component->get('donorsList');
    expect($list->total())->toBe(1);
    expect($list->first()->first_name)->toBe('John');
});

test('donors list can be filtered by trend', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // With 90-day period: current=0-90, previous=91-181

    // Member 1: increasing trend
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 200.00,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);

    // Member 2: declining trend
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 200.00,
        'donation_date' => now()->subDays(100),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 50.00,
        'donation_date' => now()->subDays(10),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch])
        ->set('donorTrendFilter', 'increasing');

    $list = $component->get('donorsList');
    expect($list->total())->toBe(1);
    expect($list->first()->trend)->toBe('increasing');
});

test('donors list can be sorted', function (): void {
    $member1 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);
    $member2 = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member1->id,
        'amount' => 100.00,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member2->id,
        'amount' => 500.00,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch])
        ->set('donorSortBy', 'lifetime_total')
        ->set('donorSortDirection', 'desc');

    $list = $component->get('donorsList');
    expect((float) $list->first()->lifetime_total)->toBe(500.0);
});

// Engagement Alerts Tests

test('engagement alerts returns correct structure', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $alerts = $component->get('engagementAlerts');
    expect($alerts)->toHaveKeys([
        'lapsing',
        'declining',
        'at_risk_major',
        'potential_major',
        'lapsing_count',
        'declining_count',
        'at_risk_count',
        'potential_count',
    ]);
});

// Retention Trend Data Tests

test('retention trend data returns correct structure', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $trendData = $component->get('retentionTrendData');
    expect($trendData)->toHaveKeys(['labels', 'data']);
    expect(count($trendData['labels']))->toBe(12);
    expect(count($trendData['data']))->toBe(12);
});

// Period Selection Tests

test('period can be changed', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch])
        ->assertSet('period', 90)
        ->call('setPeriod', 30)
        ->assertSet('period', 30);
});

// Branch Scoping Tests

test('data is scoped to current branch', function (): void {
    $otherBranch = Branch::factory()->create();
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    // Donation in current branch
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);

    // Donation in other branch (should not be included)
    Donation::factory()->create([
        'branch_id' => $otherBranch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => false,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $list = $component->get('donorsList');
    expect($list->total())->toBe(1);
});

test('anonymous donations are excluded from donor analysis', function (): void {
    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'donation_date' => now()->subDays(30),
        'is_anonymous' => true, // Anonymous
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    expect($metrics['current_period_total'])->toBe(0);
});

// Empty State Tests

test('handles empty data gracefully', function (): void {
    $component = Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch]);

    $metrics = $component->get('retentionMetrics');
    expect($metrics['returning_donors'])->toBe(0);
    expect($metrics['new_donors'])->toBe(0);
    expect($metrics['lapsed_donors'])->toBe(0);
    expect($metrics['retention_rate'])->toBe(0);

    $trends = $component->get('givingTrends');
    expect($trends['increasing_count'])->toBe(0);
    expect($trends['declining_count'])->toBe(0);
    expect($trends['consistent_count'])->toBe(0);

    $segments = $component->get('donorSegments');
    expect($segments['major']['count'])->toBe(0);
    expect($segments['regular']['count'])->toBe(0);
    expect($segments['occasional']['count'])->toBe(0);
});

// Component Renders Tests

test('component renders successfully', function (): void {
    Livewire::actingAs($this->adminUser)
        ->test(DonorEngagement::class, ['branch' => $this->branch])
        ->assertStatus(200)
        ->assertSee('Donor Engagement')
        ->assertSee('Donor Retention')
        ->assertSee('Giving Trends')
        ->assertSee('Donor Segments')
        ->assertSee('Engagement Alerts')
        ->assertSee('Individual Donors');
});
