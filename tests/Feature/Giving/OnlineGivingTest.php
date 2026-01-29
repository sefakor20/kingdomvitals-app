<?php

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Livewire\Giving\PublicGivingForm;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\PaymentTransaction;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
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

test('giving page is accessible without authentication', function (): void {
    // Configure Paystack for the branch
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    $this->get(route('giving.form', $this->branch))
        ->assertSuccessful()
        ->assertSeeLivewire(PublicGivingForm::class);
});

test('giving page shows not configured message when paystack is not set up', function (): void {
    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->assertSee('Online giving is not configured');
});

test('giving page shows form when paystack is configured', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->assertDontSee('Online giving is not configured')
        ->assertSee('Donation Amount');
});

// ============================================
// FORM INTERACTION TESTS
// ============================================

test('can set amount using preset buttons', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->call('setAmount', 50)
        ->assertSet('amount', '50');
});

test('donation types are available in form', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    $component = Livewire::test(PublicGivingForm::class, ['branch' => $this->branch]);

    expect($component->instance()->donationTypes)->toBe(DonationType::cases());
});

// ============================================
// VALIDATION TESTS
// ============================================

test('email is required for donation', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', 'John Doe')
        ->set('amount', '100')
        ->set('donationType', 'offering')
        ->call('initializePayment')
        ->assertHasErrors(['donorEmail']);
});

test('amount is required for donation', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', 'John Doe')
        ->set('donorEmail', 'john@example.com')
        ->set('donationType', 'offering')
        ->call('initializePayment')
        ->assertHasErrors(['amount']);
});

test('amount must be at least 1', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', 'John Doe')
        ->set('donorEmail', 'john@example.com')
        ->set('amount', '0.50')
        ->set('donationType', 'offering')
        ->call('initializePayment')
        ->assertHasErrors(['amount']);
});

test('donor name is required unless anonymous', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    // Without anonymous, name is required
    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', '')
        ->set('donorEmail', 'john@example.com')
        ->set('amount', '100')
        ->set('donationType', 'offering')
        ->set('isAnonymous', false)
        ->call('initializePayment')
        ->assertHasErrors(['donorName']);

    // With anonymous, name is not required
    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', '')
        ->set('donorEmail', 'john@example.com')
        ->set('amount', '100')
        ->set('donationType', 'offering')
        ->set('isAnonymous', true)
        ->call('initializePayment')
        ->assertHasNoErrors(['donorName']);
});

// ============================================
// PAYMENT INITIALIZATION TESTS
// ============================================

test('initialize payment creates transaction and dispatches event', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorName', 'John Doe')
        ->set('donorEmail', 'john@example.com')
        ->set('donorPhone', '0241234567')
        ->set('amount', '100')
        ->set('donationType', 'tithe')
        ->call('initializePayment')
        ->assertDispatched('open-paystack');

    // Check transaction was created
    $transaction = PaymentTransaction::where('branch_id', $this->branch->id)->first();
    expect($transaction)->not->toBeNull();
    expect((float) $transaction->amount)->toBe(100.0);
    expect($transaction->status)->toBe(PaymentTransactionStatus::Pending);
    expect($transaction->metadata['donor_name'])->toBe('John Doe');
    expect($transaction->metadata['donor_email'])->toBe('john@example.com');
});

test('anonymous donation stores Anonymous as donor name in metadata', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('donorEmail', 'john@example.com')
        ->set('amount', '50')
        ->set('donationType', 'offering')
        ->set('isAnonymous', true)
        ->call('initializePayment')
        ->assertDispatched('open-paystack');

    $transaction = PaymentTransaction::where('branch_id', $this->branch->id)->first();
    expect($transaction->metadata['donor_name'])->toBe('Anonymous');
    expect($transaction->metadata['is_anonymous'])->toBeTrue();
});

// ============================================
// PAYMENT SUCCESS TESTS
// ============================================

test('handle payment success creates donation record', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    // Create a pending transaction
    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'test-ref-123',
        'amount' => 100,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'donor_phone' => '0241234567',
            'donation_type' => 'tithe',
            'is_anonymous' => false,
            'is_recurring' => false,
        ],
    ]);

    // Mock Paystack verification
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 12345,
                'status' => 'success',
                'amount' => 10000,
                'channel' => 'mobile_money',
                'customer' => [
                    'customer_code' => 'CUS_123',
                ],
            ],
        ]),
    ]);

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->call('handlePaymentSuccess', 'test-ref-123')
        ->assertSet('showThankYou', true);

    // Check transaction was updated
    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentTransactionStatus::Success);
    expect($transaction->paystack_transaction_id)->toBe('12345');
    expect($transaction->channel)->toBe('mobile_money');

    // Check donation was created
    $donation = Donation::where('branch_id', $this->branch->id)->first();
    expect($donation)->not->toBeNull();
    expect((float) $donation->amount)->toBe(100.0);
    expect($donation->payment_method)->toBe(PaymentMethod::Paystack);
    expect($donation->donor_name)->toBe('John Doe');
    expect($donation->donor_email)->toBe('john@example.com');
    expect($donation->reference_number)->toBe('test-ref-123');
});

test('handle payment closed shows error message', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->call('handlePaymentClosed')
        ->assertSet('errorMessage', 'Payment was not completed. Please try again.');
});

// ============================================
// GIVE AGAIN TESTS
// ============================================

test('give again resets form', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->set('amount', '100')
        ->set('donationType', 'tithe')
        ->set('isAnonymous', true)
        ->set('showThankYou', true)
        ->call('giveAgain')
        ->assertSet('amount', '')
        ->assertSet('donationType', 'offering')
        ->assertSet('isAnonymous', false)
        ->assertSet('showThankYou', false);
});

// ============================================
// WEBHOOK TESTS
// ============================================

test('webhook handles charge success', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_456'));
    $this->branch->save();

    // Create a pending transaction
    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'webhook-test-ref',
        'amount' => 200,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [
            'donor_name' => 'Jane Doe',
            'donor_email' => 'jane@example.com',
            'donation_type' => 'offering',
            'is_anonymous' => false,
            'is_recurring' => false,
        ],
    ]);

    // Mock Paystack verification
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 67890,
                'status' => 'success',
                'amount' => 20000,
                'channel' => 'card',
                'customer' => [
                    'customer_code' => 'CUS_456',
                ],
            ],
        ]),
    ]);

    $payload = json_encode([
        'event' => 'charge.success',
        'data' => [
            'reference' => 'webhook-test-ref',
            'amount' => 20000,
            'status' => 'success',
        ],
    ]);

    $signature = hash_hmac('sha512', $payload, 'sk_test_456');

    $this->postJson(route('webhooks.paystack'), json_decode($payload, true), [
        'x-paystack-signature' => $signature,
    ])->assertSuccessful();

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentTransactionStatus::Success);

    $donation = Donation::where('reference_number', 'webhook-test-ref')->first();
    expect($donation)->not->toBeNull();
    expect((float) $donation->amount)->toBe(200.0);
});

test('webhook rejects invalid signature', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_456'));
    $this->branch->save();

    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'invalid-sig-ref',
        'amount' => 100,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [],
    ]);

    $this->postJson(route('webhooks.paystack'), [
        'event' => 'charge.success',
        'data' => [
            'reference' => 'invalid-sig-ref',
        ],
    ], [
        'x-paystack-signature' => 'invalid-signature',
    ])->assertStatus(401);
});

test('webhook handles charge failed', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_789'));
    $this->branch->save();

    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'failed-ref',
        'amount' => 100,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [],
    ]);

    $payload = json_encode([
        'event' => 'charge.failed',
        'data' => [
            'reference' => 'failed-ref',
            'gateway_response' => 'Declined',
        ],
    ]);

    $signature = hash_hmac('sha512', $payload, 'sk_test_789');

    $this->postJson(route('webhooks.paystack'), json_decode($payload, true), [
        'x-paystack-signature' => $signature,
    ])->assertSuccessful();

    $transaction->refresh();
    expect($transaction->status)->toBe(PaymentTransactionStatus::Failed);
});

// ============================================
// PAYSTACK SERVICE TESTS
// ============================================

test('paystack service converts amount to kobo correctly', function (): void {
    expect(PaystackService::toKobo(100.00))->toBe(10000);
    expect(PaystackService::toKobo(50.50))->toBe(5050);
    expect(PaystackService::toKobo(0.01))->toBe(1);
});

test('paystack service converts kobo to amount correctly', function (): void {
    expect(PaystackService::fromKobo(10000))->toBe(100.0);
    expect(PaystackService::fromKobo(5050))->toBe(50.5);
    expect(PaystackService::fromKobo(1))->toBe(0.01);
});

test('paystack service for branch loads encrypted credentials', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_abc'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_xyz'));
    $this->branch->setSetting('paystack_test_mode', true);
    $this->branch->save();

    $service = PaystackService::forBranch($this->branch);

    expect($service->isConfigured())->toBeTrue();
    expect($service->getPublicKey())->toBe('pk_test_abc');
    expect($service->isTestMode())->toBeTrue();
});

test('paystack service is not configured without credentials', function (): void {
    $service = PaystackService::forBranch($this->branch);

    expect($service->isConfigured())->toBeFalse();
});

// ============================================
// AUTO-LINKING TESTS
// ============================================

test('donation is auto-linked to member when donor email matches', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    // Create a member with matching email
    $member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'member@example.com',
    ]);

    // Create a pending transaction
    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'auto-link-test-ref',
        'amount' => 100,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [
            'donor_name' => 'Test Member',
            'donor_email' => 'member@example.com',
            'donation_type' => 'tithe',
            'is_anonymous' => false,
            'is_recurring' => false,
        ],
    ]);

    // Mock Paystack verification
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 99999,
                'status' => 'success',
                'amount' => 10000,
                'channel' => 'mobile_money',
                'customer' => [
                    'customer_code' => 'CUS_AUTO',
                ],
            ],
        ]),
    ]);

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->call('handlePaymentSuccess', 'auto-link-test-ref')
        ->assertSet('showThankYou', true);

    // Check donation was created and linked to member
    $donation = Donation::where('reference_number', 'auto-link-test-ref')->first();
    expect($donation)->not->toBeNull();
    expect($donation->member_id)->toBe($member->id);
});

test('donation is not linked when no matching member exists', function (): void {
    $this->branch->setSetting('paystack_public_key', Crypt::encryptString('pk_test_123'));
    $this->branch->setSetting('paystack_secret_key', Crypt::encryptString('sk_test_123'));
    $this->branch->save();

    // Create a pending transaction with email that doesn't match any member
    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'paystack_reference' => 'no-link-test-ref',
        'amount' => 50,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'metadata' => [
            'donor_name' => 'Guest Donor',
            'donor_email' => 'guest@example.com',
            'donation_type' => 'offering',
            'is_anonymous' => false,
            'is_recurring' => false,
        ],
    ]);

    // Mock Paystack verification
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'id' => 88888,
                'status' => 'success',
                'amount' => 5000,
                'channel' => 'card',
                'customer' => [
                    'customer_code' => 'CUS_GUEST',
                ],
            ],
        ]),
    ]);

    Livewire::test(PublicGivingForm::class, ['branch' => $this->branch])
        ->call('handlePaymentSuccess', 'no-link-test-ref')
        ->assertSet('showThankYou', true);

    // Check donation was created but NOT linked to a member
    $donation = Donation::where('reference_number', 'no-link-test-ref')->first();
    expect($donation)->not->toBeNull();
    expect($donation->member_id)->toBeNull();
});
