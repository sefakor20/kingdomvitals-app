<?php

use App\Enums\BranchRole;
use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use App\Livewire\Donations\DonationIndex;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
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
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// PAGE ACCESS TESTS
// ============================================

test('authenticated user with branch access can view donations page', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/donations")
        ->assertOk()
        ->assertSeeLivewire(DonationIndex::class);
});

test('user without branch access cannot view donations page', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $otherBranch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user)
        ->get("/branches/{$this->branch->id}/donations")
        ->assertForbidden();
});

test('unauthenticated user cannot view donations page', function () {
    $this->get("/branches/{$this->branch->id}/donations")
        ->assertRedirect('/login');
});

// ============================================
// VIEW DONATIONS AUTHORIZATION TESTS
// ============================================

test('admin can view donations list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->assertSee(number_format((float) $donation->amount, 2));
});

test('volunteer can view donations list', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->assertSee(number_format((float) $donation->amount, 2));
});

// ============================================
// CREATE DONATION AUTHORIZATION TESTS
// ============================================

test('admin can create a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('amount', '500.00')
        ->set('donation_type', 'tithe')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->set('donor_name', 'John Doe')
        ->call('store')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertDispatched('donation-created');

    expect(Donation::where('donor_name', 'John Doe')->exists())->toBeTrue();
});

test('manager can create a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('amount', '1000.00')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'mobile_money')
        ->set('donation_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Donation::where('amount', 1000.00)->exists())->toBeTrue();
});

test('staff can create a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('amount', '250.00')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasNoErrors();

    expect(Donation::where('amount', 250.00)->exists())->toBeTrue();
});

test('volunteer cannot create a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertForbidden();
});

// ============================================
// CREATE DONATION WITH MEMBER TESTS
// ============================================

test('can create donation linked to member', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $member = Member::factory()->create(['primary_branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '750.00')
        ->set('donation_type', 'tithe')
        ->set('payment_method', 'bank_transfer')
        ->set('donation_date', now()->format('Y-m-d'))
        ->set('member_id', $member->id)
        ->call('store')
        ->assertHasNoErrors();

    $donation = Donation::where('amount', 750.00)->first();
    expect($donation->member_id)->toBe($member->id);
});

test('can create anonymous donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '300.00')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->set('is_anonymous', true)
        ->call('store')
        ->assertHasNoErrors();

    $donation = Donation::where('amount', 300.00)->first();
    expect($donation->is_anonymous)->toBeTrue();
    expect($donation->member_id)->toBeNull();
    expect($donation->donor_name)->toBeNull();
});

// ============================================
// UPDATE DONATION AUTHORIZATION TESTS
// ============================================

test('admin can update a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->assertSet('showEditModal', true)
        ->set('amount', '999.99')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false)
        ->assertDispatched('donation-updated');

    expect((float) $donation->fresh()->amount)->toBe(999.99);
});

test('manager can update a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->assertSet('showEditModal', true)
        ->set('amount', '888.88')
        ->call('update')
        ->assertHasNoErrors();

    expect((float) $donation->fresh()->amount)->toBe(888.88);
});

test('staff can update a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->set('amount', '777.77')
        ->call('update')
        ->assertHasNoErrors();

    expect((float) $donation->fresh()->amount)->toBe(777.77);
});

test('volunteer cannot update a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->assertForbidden();
});

// ============================================
// DELETE DONATION AUTHORIZATION TESTS
// ============================================

test('admin can delete a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);
    $donationId = $donation->id;

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->assertSet('showDeleteModal', true)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertDispatched('donation-deleted');

    expect(Donation::find($donationId))->toBeNull();
});

test('manager can delete a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Manager,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);
    $donationId = $donation->id;

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Donation::find($donationId))->toBeNull();
});

test('staff cannot delete a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->assertForbidden();
});

test('volunteer cannot delete a donation', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->assertForbidden();
});

// ============================================
// SEARCH AND FILTER TESTS
// ============================================

test('can search donations by donor name', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $searchable = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donor_name' => 'SearchableDonor',
        'is_anonymous' => false,
        'amount' => 100,
    ]);

    $hidden = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donor_name' => 'HiddenDonor',
        'is_anonymous' => false,
        'amount' => 200,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->set('search', 'SearchableDonor')
        ->assertSee('SearchableDonor')
        // Check the hidden donation row is not displayed in the table
        ->assertSeeHtml('donation-'.$searchable->id)
        ->assertDontSeeHtml('donation-'.$hidden->id);
});

test('can filter donations by type', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Donation::factory()->tithe()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000,
    ]);

    Donation::factory()->offering()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->set('typeFilter', 'tithe');

    expect($component->instance()->donations->count())->toBe(1);
    expect($component->instance()->donations->first()->donation_type)->toBe(DonationType::Tithe);
});

test('can filter donations by payment method', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Donation::factory()->cash()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100,
    ]);

    Donation::factory()->mobileMoney()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->set('paymentMethodFilter', 'cash');

    expect($component->instance()->donations->count())->toBe(1);
    expect($component->instance()->donations->first()->payment_method)->toBe(PaymentMethod::Cash);
});

test('can filter donations by date range', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now()->subDays(5),
        'amount' => 100,
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now()->subDays(30),
        'amount' => 200,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'));

    expect($component->instance()->donations->count())->toBe(1);
    expect((float) $component->instance()->donations->first()->amount)->toBe(100.0);
});

test('can filter anonymous donations', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Donation::factory()->anonymous()->create([
        'branch_id' => $this->branch->id,
        'amount' => 100,
    ]);

    Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'is_anonymous' => false,
        'amount' => 200,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->set('memberFilter', 'anonymous');

    expect($component->instance()->donations->count())->toBe(1);
    expect($component->instance()->donations->first()->is_anonymous)->toBeTrue();
});

// ============================================
// STATS TESTS
// ============================================

test('donation stats are calculated correctly', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    Donation::factory()->tithe()->thisMonth()->create([
        'branch_id' => $this->branch->id,
        'amount' => 1000,
    ]);

    Donation::factory()->offering()->thisMonth()->create([
        'branch_id' => $this->branch->id,
        'amount' => 500,
    ]);

    Donation::factory()->tithe()->create([
        'branch_id' => $this->branch->id,
        'amount' => 200,
        'donation_date' => now()->subMonths(2),
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch]);
    $stats = $component->instance()->donationStats;

    expect($stats['total'])->toBe(1700.0);
    expect($stats['count'])->toBe(3);
    expect($stats['thisMonth'])->toBe(1500.0);
    expect($stats['tithes'])->toBe(1200.0);
    expect($stats['offerings'])->toBe(500.0);
});

// ============================================
// VALIDATION TESTS
// ============================================

test('amount is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['amount']);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '0')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['amount']);
});

test('donation date is required', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '100')
        ->set('donation_type', 'offering')
        ->set('payment_method', 'cash')
        ->set('donation_date', '')
        ->call('store')
        ->assertHasErrors(['donation_date']);
});

test('donation type must be valid', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->set('amount', '100')
        ->set('donation_type', 'invalid_type')
        ->set('payment_method', 'cash')
        ->set('donation_date', now()->format('Y-m-d'))
        ->call('store')
        ->assertHasErrors(['donation_type']);
});

// ============================================
// MODAL CANCEL TESTS
// ============================================

test('cancel create modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('create')
        ->assertSet('showCreateModal', true)
        ->set('amount', '100')
        ->call('cancelCreate')
        ->assertSet('showCreateModal', false)
        ->assertSet('amount', '');
});

test('cancel edit modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->assertSet('showEditModal', true)
        ->call('cancelEdit')
        ->assertSet('showEditModal', false)
        ->assertSet('editingDonation', null);
});

test('cancel delete modal closes modal', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->assertSet('showDeleteModal', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('deletingDonation', null);
});

// ============================================
// DISPLAY TESTS
// ============================================

test('empty state is shown when no donations exist', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->assertSee('No donations found');
});

test('create button is visible for users with create permission', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff,
    ]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->assertSee('Record Donation');
});

test('create button is hidden for volunteers', function () {
    $user = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(DonationIndex::class, ['branch' => $this->branch]);
    expect($component->instance()->canCreate)->toBeFalse();
});

// ============================================
// CROSS-BRANCH AUTHORIZATION TESTS
// ============================================

test('user cannot update donation from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('edit', $donation)
        ->assertForbidden();
});

test('user cannot delete donation from different branch', function () {
    $user = User::factory()->create();
    $otherBranch = Branch::factory()->create();

    UserBranchAccess::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);

    $donation = Donation::factory()->create(['branch_id' => $otherBranch->id]);

    $this->actingAs($user);

    Livewire::test(DonationIndex::class, ['branch' => $this->branch])
        ->call('confirmDelete', $donation)
        ->assertForbidden();
});
