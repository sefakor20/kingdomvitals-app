<?php

use App\Enums\DonationType;
use App\Livewire\Giving\MemberGivingHistory;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);

    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();
    $this->user = User::factory()->create(['email' => 'member@example.com']);
    $this->member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => $this->user->email,
    ]);
});

afterEach(function (): void {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('giving history page requires authentication', function (): void {
    $this->get(route('giving.history', $this->branch))
        ->assertRedirect(route('login'));
});

test('authenticated user can access giving history page', function (): void {
    $this->actingAs($this->user);

    $this->get(route('giving.history', $this->branch))
        ->assertSuccessful()
        ->assertSeeLivewire(MemberGivingHistory::class);
});

// ============================================
// DONATION DISPLAY TESTS
// ============================================

test('member sees their own donations by member_id', function (): void {
    $this->actingAs($this->user);

    // Create donation linked to member
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 100,
        'donation_type' => DonationType::Tithe,
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertSee('100.00');
});

test('member sees their own donations by email', function (): void {
    $this->actingAs($this->user);

    // Create donation linked by email (guest donation)
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => null,
        'donor_email' => $this->user->email,
        'amount' => 200,
        'donation_type' => DonationType::Offering,
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertSee('200.00');
});

test('member does not see other members donations', function (): void {
    $this->actingAs($this->user);

    $otherMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'other@example.com',
    ]);

    // Create donation for other member
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $otherMember->id,
        'donor_email' => 'other@example.com',
        'amount' => 999.99,
        'donation_type' => DonationType::Special,
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertDontSee('999.99');
});

// ============================================
// STATS TESTS
// ============================================

test('giving stats are calculated correctly', function (): void {
    $this->actingAs($this->user);

    // Create donations for this year
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 100,
        'donation_date' => now(),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 50,
        'donation_date' => now(),
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch]);

    $stats = $component->instance()->givingStats;

    expect((float) $stats['total'])->toBe(150.0);
    expect($stats['count'])->toBe(2);
    expect((float) $stats['thisYear'])->toBe(150.0);
    expect($stats['yearCount'])->toBe(2);
});

// ============================================
// FILTER TESTS
// ============================================

test('can filter by donation type', function (): void {
    $this->actingAs($this->user);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 100,
        'donation_type' => DonationType::Tithe,
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 200,
        'donation_type' => DonationType::Offering,
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->set('typeFilter', 'tithe');

    // Verify donations collection only contains tithe donations
    $donations = $component->instance()->donations;
    expect($donations)->toHaveCount(1);
    expect((float) $donations->first()->amount)->toBe(100.0);
});

test('can filter by date range', function (): void {
    $this->actingAs($this->user);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 100,
        'donation_date' => now(),
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 200,
        'donation_date' => now()->subMonths(3),
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subDays(7)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('100.00')
        ->assertDontSee('200.00');
});

test('can clear filters', function (): void {
    $this->actingAs($this->user);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 123.45,
        'donation_type' => DonationType::Tithe,
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertSee('123.45'); // Initially visible

    // After filtering, the donation list should be empty (but stats still show)
    $component->set('typeFilter', 'offering');
    expect($component->instance()->donations)->toHaveCount(0);

    // After clearing filters, donation should be back
    $component->call('clearFilters')
        ->assertSet('typeFilter', '')
        ->assertSee('123.45');
});

// ============================================
// YEAR SELECTOR TESTS
// ============================================

test('can change year for stats', function (): void {
    $this->actingAs($this->user);

    // Create donation for last year
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 500,
        'donation_date' => now()->subYear(),
    ]);

    // Create donation for this year
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 100,
        'donation_date' => now(),
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch]);

    // Default year should be current year
    $stats = $component->instance()->givingStats;
    expect((float) $stats['thisYear'])->toBe(100.0);

    // Change to last year
    $component->call('setYear', (string) now()->subYear()->year);
    $stats = $component->instance()->givingStats;
    expect((float) $stats['thisYear'])->toBe(500.0);
});

// ============================================
// RECEIPT DOWNLOAD TESTS
// ============================================

test('member can download receipt for their own donation', function (): void {
    $this->actingAs($this->user);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'donor_email' => $this->user->email,
        'amount' => 100,
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch]);

    // This should not throw an exception
    $response = $component->call('downloadReceipt', $donation);
    expect($response)->not->toBeNull();
});

test('member cannot download receipt for another members donation', function (): void {
    $this->actingAs($this->user);

    $otherMember = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'other@example.com',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $otherMember->id,
        'donor_email' => 'other@example.com',
        'amount' => 100,
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->call('downloadReceipt', $donation)
        ->assertForbidden();
});

// ============================================
// RECURRING DONATIONS TESTS
// ============================================

test('recurring donations are displayed', function (): void {
    $this->actingAs($this->user);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $this->member->id,
        'amount' => 50,
        'is_recurring' => true,
        'recurring_interval' => 'monthly',
        'paystack_subscription_code' => 'SUB_123',
    ]);

    $component = Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch]);

    expect($component->instance()->recurringDonations)->toHaveCount(1);
});

// ============================================
// EMPTY STATE TESTS
// ============================================

test('shows empty state when no donations', function (): void {
    $this->actingAs($this->user);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertSee('No donations found')
        ->assertSee('Make a Donation');
});

// ============================================
// CROSS-BRANCH SECURITY TESTS
// ============================================

test('member only sees donations from current branch', function (): void {
    $this->actingAs($this->user);

    $otherBranch = Branch::factory()->create();

    // Create donation in other branch
    Donation::factory()->create([
        'branch_id' => $otherBranch->id,
        'donor_email' => $this->user->email,
        'amount' => 999.99,
    ]);

    // Create donation in current branch
    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donor_email' => $this->user->email,
        'amount' => 100,
    ]);

    Livewire::test(MemberGivingHistory::class, ['branch' => $this->branch])
        ->assertSee('100.00')
        ->assertDontSee('999.99');
});
