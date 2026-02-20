<?php

declare(strict_types=1);

use App\Enums\DonationType;
use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\AI\GivingTrendService;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = new GivingTrendService;
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// ============================================
// SINGLE MEMBER ANALYSIS
// ============================================

it('analyzes giving trends for a member with donations', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Create monthly donations over 6 months
    for ($i = 0; $i < 6; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->tithe()
            ->create([
                'amount' => 100,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->memberId)->toBe($member->id);
    expect($trend->donationCount)->toBe(6);
    expect($trend->totalGiven)->toBe(600.0);
    expect($trend->averageGift)->toBe(100.0);
    expect($trend->consistencyScore)->toBeGreaterThan(0);
    expect($trend->preferredType)->toBe(DonationType::Tithe);
});

it('returns empty trend for member with no donations', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->donationCount)->toBe(0);
    expect($trend->totalGiven)->toBe(0.0);
    expect($trend->consistencyScore)->toBe(0.0);
    expect($trend->trend)->toBe('lapsed');
    expect($trend->confidenceScore)->toBe(0);
});

it('calculates growth rate correctly for growing donor', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Older period: $100/month for 6 months
    for ($i = 6; $i < 12; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create([
                'amount' => 100,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    // Recent period: $200/month for 6 months (100% increase)
    for ($i = 0; $i < 6; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create([
                'amount' => 200,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->growthRate)->toBeGreaterThan(50);
    expect($trend->trend)->toBe('growing');
    expect($trend->isGrowing())->toBeTrue();
});

it('calculates growth rate correctly for declining donor', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Older period: $200/month for 6 months
    for ($i = 6; $i < 12; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create([
                'amount' => 200,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    // Recent period: $100/month for 6 months (50% decrease)
    for ($i = 0; $i < 6; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create([
                'amount' => 100,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->growthRate)->toBeLessThan(-20);
    expect($trend->trend)->toBe('declining');
    expect($trend->isDeclining())->toBeTrue();
});

it('identifies new donors correctly', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Single donation within 90 days
    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'amount' => 100,
            'donation_date' => now()->subDays(30),
        ]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->trend)->toBe('new');
    expect($trend->isNewDonor())->toBeTrue();
});

it('identifies lapsed donors correctly', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Donation more than 90 days ago
    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'amount' => 100,
            'donation_date' => now()->subDays(120),
        ]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->trend)->toBe('lapsed');
    expect($trend->isLapsed())->toBeTrue();
    expect($trend->daysSinceLastDonation)->toBeGreaterThan(90);
});

it('detects preferred donation type', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // More tithes than offerings
    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->tithe()
        ->count(5)
        ->create(['donation_date' => now()->subDays(10)]);

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->offering()
        ->count(2)
        ->create(['donation_date' => now()->subDays(10)]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->preferredType)->toBe(DonationType::Tithe);
});

it('detects preferred payment method', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // More mobile money than cash
    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->mobileMoney()
        ->count(5)
        ->create(['donation_date' => now()->subDays(10)]);

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->cash()
        ->count(2)
        ->create(['donation_date' => now()->subDays(10)]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->preferredMethod)->toBe(PaymentMethod::MobileMoney);
});

it('calculates consistency score higher for regular donors', function (): void {
    $regularMember = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    $sporadicMember = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Regular: monthly donations
    for ($i = 0; $i < 12; $i++) {
        Donation::factory()
            ->for($this->branch)
            ->for($regularMember)
            ->create([
                'amount' => 100,
                'donation_date' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    // Sporadic: only 3 donations spread out
    Donation::factory()
        ->for($this->branch)
        ->for($sporadicMember)
        ->create(['amount' => 100, 'donation_date' => now()->subMonths(1)]);

    Donation::factory()
        ->for($this->branch)
        ->for($sporadicMember)
        ->create(['amount' => 100, 'donation_date' => now()->subMonths(6)]);

    Donation::factory()
        ->for($this->branch)
        ->for($sporadicMember)
        ->create(['amount' => 100, 'donation_date' => now()->subMonths(11)]);

    $regularTrend = $this->service->analyzeForMember($regularMember, 12);
    $sporadicTrend = $this->service->analyzeForMember($sporadicMember, 12);

    expect($regularTrend->consistencyScore)->toBeGreaterThan($sporadicTrend->consistencyScore);
});

// ============================================
// BRANCH ANALYSIS
// ============================================

it('analyzes all donors in a branch', function (): void {
    // Create 3 members with donations
    for ($i = 0; $i < 3; $i++) {
        $member = Member::factory()
            ->for($this->branch, 'primaryBranch')
            ->create(['status' => MembershipStatus::Active]);

        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create(['donation_date' => now()->subDays(10)]);
    }

    $trends = $this->service->analyzeForBranch($this->branch, 12);

    expect($trends)->toHaveCount(3);
});

it('assigns donor tiers correctly', function (): void {
    // Create 10 members with varying donation amounts
    for ($i = 1; $i <= 10; $i++) {
        $member = Member::factory()
            ->for($this->branch, 'primaryBranch')
            ->create(['status' => MembershipStatus::Active]);

        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create([
                'amount' => $i * 100, // 100, 200, 300, ..., 1000
                'donation_date' => now()->subDays(10),
            ]);
    }

    $trends = $this->service->analyzeForBranch($this->branch, 12);

    $topTier = $trends->filter(fn ($t): bool => $t->donorTier === 'top_10')->count();
    $top25 = $trends->filter(fn ($t): bool => $t->donorTier === 'top_25')->count();

    expect($topTier)->toBe(1); // Top 10% of 10 = 1
    expect($top25)->toBeGreaterThanOrEqual(1);
});

// ============================================
// RETRIEVAL METHODS
// ============================================

it('retrieves major donors', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'status' => MembershipStatus::Active,
            'donor_tier' => 'top_10',
            'giving_consistency_score' => 85,
            'giving_analyzed_at' => now(),
        ]);

    $majorDonors = $this->service->getMajorDonors($this->branch);

    expect($majorDonors)->toHaveCount(1);
    expect($majorDonors->first()->id)->toBe($member->id);
});

it('retrieves declining donors', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'status' => MembershipStatus::Active,
            'giving_trend' => 'declining',
            'giving_growth_rate' => -35,
            'giving_analyzed_at' => now(),
        ]);

    $declining = $this->service->getDecliningDonors($this->branch);

    expect($declining)->toHaveCount(1);
    expect($declining->first()->giving_growth_rate)->toBeLessThan(-20);
});

it('retrieves growing donors', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'status' => MembershipStatus::Active,
            'giving_trend' => 'growing',
            'giving_growth_rate' => 45,
            'giving_analyzed_at' => now(),
        ]);

    $growing = $this->service->getGrowingDonors($this->branch);

    expect($growing)->toHaveCount(1);
    expect($growing->first()->giving_growth_rate)->toBeGreaterThan(20);
});

it('retrieves new donors', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create([
            'status' => MembershipStatus::Active,
            'giving_trend' => 'new',
            'giving_analyzed_at' => now(),
        ]);

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create(['donation_date' => now()->subDays(10)]);

    $newDonors = $this->service->getFirstTimeDonors($this->branch, 30);

    expect($newDonors)->toHaveCount(1);
});

// ============================================
// STATISTICS
// ============================================

it('calculates giving statistics', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create(['status' => MembershipStatus::Active]);

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->count(5)
        ->create([
            'amount' => 100,
            'donation_date' => now()->subDays(10),
        ]);

    $stats = $this->service->getGivingStatistics($this->branch, 12);

    expect($stats['total_donations'])->toBe(500.0);
    expect($stats['donation_count'])->toBe(5);
    expect($stats['average_donation'])->toBe(100.0);
    expect($stats['unique_donors'])->toBe(1);
});

it('calculates donor tier distribution', function (): void {
    // Create members with assigned tiers
    foreach (['top_10', 'top_25', 'middle', 'bottom'] as $tier) {
        $member = Member::factory()
            ->for($this->branch, 'primaryBranch')
            ->create([
                'status' => MembershipStatus::Active,
                'donor_tier' => $tier,
            ]);

        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create(['donation_date' => now()->subDays(10)]);
    }

    $distribution = $this->service->getDonorTierDistribution($this->branch);

    expect($distribution)->toHaveKey('top_10');
    expect($distribution)->toHaveKey('top_25');
    expect($distribution)->toHaveKey('middle');
    expect($distribution)->toHaveKey('bottom');
});

// ============================================
// MEMBER UPDATE
// ============================================

it('updates member with giving trend data', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->count(6)
        ->create([
            'amount' => 100,
            'donation_date' => now()->subDays(10),
        ]);

    $trend = $this->service->analyzeForMember($member, 12);
    $this->service->updateMemberGivingData($member, $trend);

    $member->refresh();

    expect($member->giving_consistency_score)->toBeGreaterThan(0);
    expect($member->giving_trend)->not->toBeNull();
    expect($member->giving_analyzed_at)->not->toBeNull();
});

it('processes entire branch and updates all members', function (): void {
    // Create 3 members with donations
    $members = collect();
    for ($i = 0; $i < 3; $i++) {
        $member = Member::factory()
            ->for($this->branch, 'primaryBranch')
            ->create(['status' => MembershipStatus::Active]);

        Donation::factory()
            ->for($this->branch)
            ->for($member)
            ->create(['donation_date' => now()->subDays(10)]);

        $members->push($member);
    }

    $processed = $this->service->processBranch($this->branch, 12);

    expect($processed)->toBe(3);

    foreach ($members as $member) {
        $member->refresh();
        expect($member->giving_analyzed_at)->not->toBeNull();
    }
});

// ============================================
// DTO METHODS
// ============================================

it('provides correct trend labels and colors', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create(['donation_date' => now()->subDays(10)]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->trendLabel())->toBeIn(['Growing', 'Stable', 'Declining', 'New Donor', 'Lapsed']);
    expect($trend->trendColor())->toBeIn(['green', 'zinc', 'red', 'purple', 'amber']);
    expect($trend->trendIcon())->not->toBeEmpty();
});

it('converts trend to array correctly', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create(['donation_date' => now()->subDays(10)]);

    $trend = $this->service->analyzeForMember($member, 12);
    $array = $trend->toArray();

    expect($array)->toHaveKey('member_id');
    expect($array)->toHaveKey('consistency_score');
    expect($array)->toHaveKey('growth_rate');
    expect($array)->toHaveKey('donor_tier');
    expect($array)->toHaveKey('trend');
    expect($array)->toHaveKey('monthly_history');
});

it('calculates monthly history correctly', function (): void {
    $member = Member::factory()
        ->for($this->branch, 'primaryBranch')
        ->create();

    // Create donations in specific months
    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'amount' => 100,
            'donation_date' => now()->startOfMonth(),
        ]);

    Donation::factory()
        ->for($this->branch)
        ->for($member)
        ->create([
            'amount' => 200,
            'donation_date' => now()->subMonth()->startOfMonth(),
        ]);

    $trend = $this->service->analyzeForMember($member, 12);

    expect($trend->monthlyHistory)->toHaveCount(12);

    // Current month should have 100
    $currentMonth = now()->format('Y-m');
    expect($trend->monthlyHistory[$currentMonth])->toBe(100.0);

    // Last month should have 200
    $lastMonth = now()->subMonth()->format('Y-m');
    expect($trend->monthlyHistory[$lastMonth])->toBe(200.0);
});
