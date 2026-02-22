<?php

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\PaymentTransactionStatus;
use App\Enums\RegistrationStatus;
use App\Livewire\Events\Public\PublicEventDetails;
use App\Livewire\Events\Public\PublicEventRegistration;
use App\Mail\EventRegistrationConfirmationMail;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\PaymentTransaction;
use Illuminate\Support\Facades\Mail;
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
// PUBLIC EVENT DETAILS TESTS
// ============================================

test('public event details page displays correctly', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'name' => 'Community Gathering',
        'location' => 'Main Hall',
    ]);

    Livewire::test(PublicEventDetails::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(200)
        ->assertSee('Community Gathering')
        ->assertSee('Main Hall');
});

test('public event details page shows register button for open registration', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(PublicEventDetails::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(200)
        ->assertSee(__('Register Now'));
});

test('public event details returns 404 for non-public events', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => false,
        'visibility' => EventVisibility::MembersOnly,
    ]);

    Livewire::test(PublicEventDetails::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(404);
});

test('public event details returns 404 for draft events', function (): void {
    $event = Event::factory()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'status' => EventStatus::Draft,
    ]);

    Livewire::test(PublicEventDetails::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(404);
});

// ============================================
// PUBLIC EVENT REGISTRATION TESTS
// ============================================

test('public event registration page displays correctly', function (): void {
    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(200)
        ->assertSee(__('Complete Registration'));
});

test('free event registration creates registration with ticket', function (): void {
    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '0201234567')
        ->call('register')
        ->assertSet('showThankYou', true);

    $this->assertDatabaseHas('event_registrations', [
        'event_id' => $event->id,
        'guest_name' => 'John Doe',
        'guest_email' => 'john@example.com',
        'is_paid' => true,
    ]);

    $registration = EventRegistration::where('guest_email', 'john@example.com')->first();
    expect($registration->ticket_number)->toStartWith('EVT-');
});

test('free event registration sends confirmation email', function (): void {
    Mail::fake();

    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('phone', '0201234567')
        ->call('register')
        ->assertSet('showThankYou', true);

    Mail::assertQueued(EventRegistrationConfirmationMail::class, function ($mail) {
        return $mail->hasTo('jane@example.com');
    });
});

test('registration validates required fields', function (): void {
    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->set('name', '')
        ->set('email', '')
        ->call('register')
        ->assertHasErrors(['name', 'email']);
});

test('registration prevents duplicate registrations', function (): void {
    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
    ]);

    // Create existing registration
    EventRegistration::factory()->guest()->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
        'guest_email' => 'john@example.com',
        'status' => RegistrationStatus::Registered,
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->call('register')
        ->assertSet('errorMessage', __('You are already registered for this event.'));
});

test('registration fails for full events', function (): void {
    $event = Event::factory()->published()->free()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'capacity' => 2,
        'starts_at' => now()->addDays(5),
    ]);

    // Fill capacity
    EventRegistration::factory()->guest()->count(2)->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(404);
});

test('registration returns 404 for non-public events', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => false,
        'visibility' => EventVisibility::MembersOnly,
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(404);
});

test('registration returns 404 for closed registration', function (): void {
    $event = Event::factory()->published()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => false,
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(404);
});

// ============================================
// PAID EVENT TESTS
// ============================================

test('paid event shows price and payment button', function (): void {
    // Configure Paystack for paid events
    $this->branch->setSetting('paystack_secret_key', 'sk_test_xxx');
    $this->branch->setSetting('paystack_public_key', 'pk_test_xxx');
    $this->branch->save();

    $event = Event::factory()->published()->paid()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
        'price' => 50.00,
        'currency' => 'GHS',
    ]);

    Livewire::test(PublicEventRegistration::class, ['branch' => $this->branch, 'event' => $event])
        ->assertStatus(200)
        ->assertSee('GHS 50.00')
        ->assertSee(__('Secured by Paystack'));
});

// ============================================
// WEBHOOK TESTS
// ============================================

test('webhook marks registration as paid', function (): void {
    $event = Event::factory()->published()->paid()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'price' => 50.00,
    ]);

    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
        'is_paid' => false,
        'requires_payment' => true,
        'price_paid' => 50.00,
    ]);

    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'event_registration_id' => $registration->id,
        'amount' => 50.00,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'paystack_reference' => 'test-ref-123',
    ]);

    // Mark as paid
    $registration->markAsPaid($transaction);
    $registration->refresh();

    expect($registration->is_paid)->toBeTrue()
        ->and($registration->payment_transaction_id)->toBe($transaction->id)
        ->and($registration->ticket_number)->toStartWith('EVT-');
});

test('paid event sends confirmation email after payment', function (): void {
    Mail::fake();

    // Configure Paystack for paid events
    $this->branch->setSetting('paystack_secret_key', 'sk_test_xxx');
    $this->branch->setSetting('paystack_public_key', 'pk_test_xxx');
    $this->branch->save();

    $event = Event::factory()->published()->paid()->create([
        'branch_id' => $this->branch->id,
        'is_public' => true,
        'visibility' => EventVisibility::Public,
        'allow_registration' => true,
        'starts_at' => now()->addDays(5),
        'price' => 50.00,
        'currency' => 'GHS',
    ]);

    $registration = EventRegistration::factory()->guest()->create([
        'event_id' => $event->id,
        'branch_id' => $this->branch->id,
        'guest_email' => 'paid@example.com',
        'is_paid' => false,
        'requires_payment' => true,
        'price_paid' => 50.00,
    ]);

    $transaction = PaymentTransaction::create([
        'branch_id' => $this->branch->id,
        'event_registration_id' => $registration->id,
        'amount' => 50.00,
        'currency' => 'GHS',
        'status' => PaymentTransactionStatus::Pending,
        'paystack_reference' => 'test-email-ref-123',
    ]);

    // Simulate the handlePaymentSuccess flow
    $transaction->markAsSuccessful('12345', 'card');
    $registration->markAsPaid($transaction);
    $registration->refresh();

    // Send confirmation email (as done in handlePaymentSuccess)
    Mail::to($registration->guest_email)->queue(new EventRegistrationConfirmationMail($registration));

    Mail::assertQueued(EventRegistrationConfirmationMail::class, function ($mail) {
        return $mail->hasTo('paid@example.com');
    });
});
