<?php

use App\Enums\BranchRole;
use App\Livewire\Donations\DonationIndex;
use App\Mail\DonationReceiptMail;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\User;
use App\Services\DonationReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
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

    // Create staff user with access
    $this->staffUser = User::factory()->create();
    $this->staffUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Staff->value,
    ]);

    // Create volunteer user (limited access)
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

// Receipt Number Generation Tests

test('receipt number is generated with correct format', function () {
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now(),
    ]);

    $service = app(DonationReceiptService::class);
    $receiptNumber = $service->generateReceiptNumber($donation);

    expect($receiptNumber)->toMatch('/^REC-[A-Z]{2}-\d{6}-\d{5}$/');
});

test('receipt numbers are sequential within branch and month', function () {
    $donation1 = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now(),
    ]);
    $donation2 = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now(),
    ]);

    $service = app(DonationReceiptService::class);
    $receipt1 = $service->generateReceiptNumber($donation1);
    $donation1->update(['receipt_number' => $receipt1]);

    $receipt2 = $service->generateReceiptNumber($donation2);

    expect((int) substr($receipt2, -5))->toBe((int) substr($receipt1, -5) + 1);
});

test('donation model generates receipt number on first access', function () {
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'donation_date' => now(),
    ]);

    expect($donation->receipt_number)->toBeNull();

    $receiptNumber = $donation->getReceiptNumber();

    expect($receiptNumber)->toMatch('/^REC-[A-Z]{2}-\d{6}-\d{5}$/');
    expect($donation->refresh()->receipt_number)->toBe($receiptNumber);
});

// Download Receipt Tests

test('admin can download donation receipt', function () {
    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('downloadReceipt', $donation)
        ->assertFileDownloaded();
});

test('volunteer can download donation receipt', function () {
    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->volunteerUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('downloadReceipt', $donation)
        ->assertFileDownloaded();
});

// Email Receipt Tests

test('admin can email donation receipt to member with email', function () {
    Mail::fake();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'donor@example.com',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('emailReceipt', $donation)
        ->assertDispatched('receipt-sent');

    Mail::assertQueued(DonationReceiptMail::class);
    expect($donation->refresh()->receipt_sent_at)->not->toBeNull();
});

test('staff can email donation receipt', function () {
    Mail::fake();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'donor@example.com',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    Livewire::actingAs($this->staffUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('emailReceipt', $donation)
        ->assertDispatched('receipt-sent');

    Mail::assertQueued(DonationReceiptMail::class);
});

test('cannot email receipt for anonymous donation', function () {
    Mail::fake();

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'is_anonymous' => true,
    ]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('emailReceipt', $donation)
        ->assertDispatched('receipt-send-failed');

    Mail::assertNothingQueued();
});

test('cannot email receipt when donor has no email', function () {
    Mail::fake();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => null,
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('emailReceipt', $donation)
        ->assertDispatched('receipt-send-failed');

    Mail::assertNothingQueued();
});

test('volunteer cannot email donation receipt', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'donor@example.com',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
    ]);

    Livewire::actingAs($this->volunteerUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('emailReceipt', $donation)
        ->assertForbidden();
});

// Bulk Operations Tests

test('can bulk download receipts as zip', function () {
    $donations = Donation::factory()->count(3)->create([
        'branch_id' => $this->branch->id,
    ]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->set('selectedDonations', $donations->pluck('id')->toArray())
        ->call('bulkDownloadReceipts')
        ->assertFileDownloaded();
});

test('can bulk email receipts to eligible donors', function () {
    Mail::fake();

    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'donor@example.com',
    ]);

    $donationWithEmail = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    $anonymousDonation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'is_anonymous' => true,
    ]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->set('selectedDonations', [$donationWithEmail->id, $anonymousDonation->id])
        ->call('bulkEmailReceipts')
        ->assertDispatched('bulk-receipts-sent');

    Mail::assertQueued(DonationReceiptMail::class, 1);
});

// Selection Tests

test('can toggle donation selection', function () {
    $donation = Donation::factory()->create(['branch_id' => $this->branch->id]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch]);

    expect($component->get('selectedDonations'))->toBeEmpty();

    $component->call('toggleDonationSelection', $donation->id);
    expect($component->get('selectedDonations'))->toContain($donation->id);

    $component->call('toggleDonationSelection', $donation->id);
    expect($component->get('selectedDonations'))->not->toContain($donation->id);
});

test('can select all donations', function () {
    Donation::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->call('selectAllDonations');

    expect(count($component->get('selectedDonations')))->toBe(3);
});

test('can deselect all donations', function () {
    $donations = Donation::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->adminUser)
        ->test(DonationIndex::class, ['branch' => $this->branch])
        ->set('selectedDonations', $donations->pluck('id')->toArray())
        ->call('deselectAllDonations')
        ->assertSet('selectedDonations', []);
});

// Donor Display Name Tests

test('getDonorDisplayName returns member name for non-anonymous donations', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'first_name' => 'John',
        'middle_name' => null,
        'last_name' => 'Doe',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    // fullName() includes middle_name which creates extra space when null
    expect($donation->getDonorDisplayName())->toBe($member->fullName());
});

test('getDonorDisplayName returns Anonymous Donor for anonymous donations', function () {
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'is_anonymous' => true,
    ]);

    expect($donation->getDonorDisplayName())->toBe(__('Anonymous Donor'));
});

test('getDonorDisplayName returns donor_name when no member', function () {
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => null,
        'donor_name' => 'External Donor',
        'is_anonymous' => false,
    ]);

    expect($donation->getDonorDisplayName())->toBe('External Donor');
});

// canSendReceipt Tests

test('canSendReceipt returns true for non-anonymous with email', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'donor@example.com',
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    expect($donation->canSendReceipt())->toBeTrue();
});

test('canSendReceipt returns false for anonymous donations', function () {
    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'is_anonymous' => true,
    ]);

    expect($donation->canSendReceipt())->toBeFalse();
});

test('canSendReceipt returns false when donor has no email', function () {
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => null,
    ]);

    $donation = Donation::factory()->create([
        'branch_id' => $this->branch->id,
        'member_id' => $member->id,
        'is_anonymous' => false,
    ]);

    expect($donation->canSendReceipt())->toBeFalse();
});
