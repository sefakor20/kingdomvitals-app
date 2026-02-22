<?php

declare(strict_types=1);

namespace App\Livewire\Events\Public;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\PaymentTransactionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Member;
use App\Models\Tenant\PaymentTransaction;
use App\Services\PaystackService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class PublicEventRegistration extends Component
{
    public Branch $branch;

    public Event $event;

    // Form fields
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    // State
    public bool $showThankYou = false;

    public ?EventRegistration $registration = null;

    public ?string $errorMessage = null;

    public function mount(Branch $branch, Event $event): void
    {
        // Validate event is publicly visible
        if (! $event->is_public || $event->visibility !== EventVisibility::Public) {
            abort(404);
        }

        // Validate event status
        if (! in_array($event->status, [EventStatus::Published, EventStatus::Ongoing])) {
            abort(404);
        }

        // Validate registration is open
        if (! $event->canRegister()) {
            abort(404);
        }

        $this->branch = $branch;
        $this->event = $event;

        // Check for Paystack configuration for paid events
        if ($event->is_paid && ! $branch->hasPaystackConfigured()) {
            $this->errorMessage = __('Online payment is not configured for this event. Please contact the organizer.');
        }
    }

    #[Computed]
    public function isPaystackConfigured(): bool
    {
        return $this->branch->hasPaystackConfigured();
    }

    #[Computed]
    public function paystackPublicKey(): string
    {
        return PaystackService::forBranch($this->branch)->getPublicKey();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => __('Please enter your full name.'),
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
        ];
    }

    /**
     * Register for a free event.
     */
    public function register(): void
    {
        $this->errorMessage = null;

        if ($this->event->is_paid) {
            $this->errorMessage = __('This is a paid event. Please use the payment button.');

            return;
        }

        $this->validate();

        // Check if already registered
        $existingRegistration = EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->where('guest_email', $this->email)
            ->whereNotIn('status', [RegistrationStatus::Cancelled])
            ->first();

        if ($existingRegistration) {
            $this->errorMessage = __('You are already registered for this event.');

            return;
        }

        // Check capacity again
        if ($this->event->is_full) {
            $this->errorMessage = __('Sorry, this event is now fully booked.');

            return;
        }

        // Try to link to existing member
        $member = Member::query()
            ->where('email', $this->email)
            ->where('primary_branch_id', $this->branch->id)
            ->first();

        // Create registration
        $this->registration = EventRegistration::create([
            'event_id' => $this->event->id,
            'branch_id' => $this->branch->id,
            'member_id' => $member?->id,
            'guest_name' => $this->name,
            'guest_email' => $this->email,
            'guest_phone' => $this->phone ?: null,
            'status' => RegistrationStatus::Registered,
            'registered_at' => now(),
            'requires_payment' => false,
            'is_paid' => true, // Free events are considered "paid"
        ]);

        $this->registration->generateTicketNumber();

        $this->showThankYou = true;
    }

    /**
     * Initialize payment for a paid event.
     */
    public function initializePayment(): void
    {
        $this->errorMessage = null;

        if (! $this->event->is_paid) {
            $this->register();

            return;
        }

        $this->validate();

        if (! $this->branch->hasPaystackConfigured()) {
            $this->errorMessage = __('Online payment is not available at this time.');

            return;
        }

        // Check if already registered
        $existingRegistration = EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->where('guest_email', $this->email)
            ->whereNotIn('status', [RegistrationStatus::Cancelled])
            ->first();

        if ($existingRegistration) {
            $this->errorMessage = __('You are already registered for this event.');

            return;
        }

        // Check capacity again
        if ($this->event->is_full) {
            $this->errorMessage = __('Sorry, this event is now fully booked.');

            return;
        }

        // Try to link to existing member
        $member = Member::query()
            ->where('email', $this->email)
            ->where('primary_branch_id', $this->branch->id)
            ->first();

        // Create pending registration
        $this->registration = EventRegistration::create([
            'event_id' => $this->event->id,
            'branch_id' => $this->branch->id,
            'member_id' => $member?->id,
            'guest_name' => $this->name,
            'guest_email' => $this->email,
            'guest_phone' => $this->phone ?: null,
            'status' => RegistrationStatus::Registered,
            'registered_at' => now(),
            'requires_payment' => true,
            'price_paid' => $this->event->price,
            'is_paid' => false,
        ]);

        // Create payment transaction
        $paystack = PaystackService::forBranch($this->branch);
        $transaction = PaymentTransaction::create([
            'branch_id' => $this->branch->id,
            'event_registration_id' => $this->registration->id,
            'amount' => $this->event->price,
            'currency' => $this->event->currency,
            'status' => PaymentTransactionStatus::Pending,
            'paystack_reference' => $paystack->generateReference(),
            'metadata' => [
                'type' => 'event_registration',
                'event_id' => $this->event->id,
                'event_name' => $this->event->name,
                'registrant_name' => $this->name,
                'registrant_email' => $this->email,
            ],
        ]);

        // Dispatch event to open Paystack popup via JavaScript
        $this->dispatch('open-paystack', [
            'key' => $paystack->getPublicKey(),
            'email' => $this->email,
            'amount' => PaystackService::toKobo((float) $this->event->price),
            'currency' => $this->event->currency,
            'reference' => $transaction->paystack_reference,
            'metadata' => [
                'transaction_id' => $transaction->id,
                'registration_id' => $this->registration->id,
                'event_name' => $this->event->name,
            ],
        ]);
    }

    /**
     * Handle successful payment callback.
     */
    public function handlePaymentSuccess(string $reference): void
    {
        $paystack = PaystackService::forBranch($this->branch);
        $result = $paystack->verifyTransaction($reference);

        if (! $result['success']) {
            $this->errorMessage = __('Payment verification failed. Please contact support.');

            return;
        }

        $transaction = PaymentTransaction::where('paystack_reference', $reference)->first();

        if (! $transaction) {
            $this->errorMessage = __('Transaction not found. Please contact support.');

            return;
        }

        $paystackData = $result['data'];

        // Update transaction
        $transaction->markAsSuccessful(
            (string) ($paystackData['id'] ?? ''),
            $paystackData['channel'] ?? null
        );

        // Mark registration as paid and generate ticket
        $this->registration = EventRegistration::find($transaction->event_registration_id);
        if ($this->registration) {
            $this->registration->markAsPaid($transaction);
            $this->registration->refresh();
        }

        $this->showThankYou = true;
    }

    /**
     * Handle payment popup closed without completing.
     */
    public function handlePaymentClosed(): void
    {
        // User closed the popup without completing payment
        // The registration is left as pending with requires_payment = true
        $this->errorMessage = __('Payment was not completed. Please try again to complete your registration.');
    }

    public function render(): View
    {
        return view('livewire.events.public.public-event-registration');
    }
}
