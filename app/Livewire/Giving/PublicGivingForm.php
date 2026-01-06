<?php

declare(strict_types=1);

namespace App\Livewire\Giving;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\PaymentTransaction;
use App\Services\PaystackService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class PublicGivingForm extends Component
{
    public Branch $branch;

    // Donor information
    public string $donorName = '';

    public string $donorEmail = '';

    public string $donorPhone = '';

    // Donation details
    public string $amount = '';

    public string $donationType = 'offering';

    public bool $isAnonymous = false;

    public bool $isRecurring = false;

    public string $recurringInterval = 'monthly';

    public string $notes = '';

    // UI state
    public bool $showThankYou = false;

    public ?Donation $lastDonation = null;

    public ?string $errorMessage = null;

    protected array $presetAmounts = [10, 20, 50, 100, 200, 500];

    public function mount(Branch $branch): void
    {
        $this->branch = $branch;

        if (! $this->branch->hasPaystackConfigured()) {
            $this->errorMessage = 'Online giving is not configured for this branch. Please contact the church administrator.';
        }
    }

    #[Computed]
    public function donationTypes(): array
    {
        return DonationType::cases();
    }

    #[Computed]
    public function presetAmountsList(): array
    {
        return $this->presetAmounts;
    }

    #[Computed]
    public function paystackPublicKey(): string
    {
        return PaystackService::forBranch($this->branch)->getPublicKey();
    }

    #[Computed]
    public function branchName(): string
    {
        return $this->branch->name;
    }

    #[Computed]
    public function isConfigured(): bool
    {
        return $this->branch->hasPaystackConfigured();
    }

    protected function rules(): array
    {
        $donationTypes = collect(DonationType::cases())->pluck('value')->implode(',');

        return [
            'donorName' => ['required_unless:isAnonymous,true', 'nullable', 'string', 'max:255'],
            'donorEmail' => ['required', 'email', 'max:255'],
            'donorPhone' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:1'],
            'donationType' => ['required', 'string', 'in:'.$donationTypes],
            'isAnonymous' => ['boolean'],
            'isRecurring' => ['boolean'],
            'recurringInterval' => ['required_if:isRecurring,true', 'nullable', 'in:weekly,monthly,yearly'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'donorName.required_unless' => 'Please enter your name or select anonymous donation.',
            'donorEmail.required' => 'We need your email to send you a receipt.',
            'donorEmail.email' => 'Please enter a valid email address.',
            'amount.required' => 'Please enter a donation amount.',
            'amount.min' => 'The minimum donation amount is GHS 1.00.',
        ];
    }

    public function setAmount(int $amount): void
    {
        $this->amount = (string) $amount;
    }

    public function initializePayment(): void
    {
        $this->errorMessage = null;
        $this->validate();

        if (! $this->branch->hasPaystackConfigured()) {
            $this->errorMessage = 'Online giving is not available at this time.';

            return;
        }

        $paystack = PaystackService::forBranch($this->branch);

        // Create a payment transaction record first
        $transaction = PaymentTransaction::create([
            'branch_id' => $this->branch->id,
            'paystack_reference' => $paystack->generateReference(),
            'amount' => (float) $this->amount,
            'currency' => 'GHS',
            'status' => PaymentTransactionStatus::Pending,
            'metadata' => [
                'donor_name' => $this->isAnonymous ? 'Anonymous' : $this->donorName,
                'donor_email' => $this->donorEmail,
                'donor_phone' => $this->donorPhone,
                'donation_type' => $this->donationType,
                'is_anonymous' => $this->isAnonymous,
                'is_recurring' => $this->isRecurring,
                'recurring_interval' => $this->isRecurring ? $this->recurringInterval : null,
                'notes' => $this->notes,
            ],
        ]);

        // Dispatch event to open Paystack popup via JavaScript
        $this->dispatch('open-paystack', [
            'key' => $paystack->getPublicKey(),
            'email' => $this->donorEmail,
            'amount' => PaystackService::toKobo((float) $this->amount),
            'currency' => 'GHS',
            'reference' => $transaction->paystack_reference,
            'metadata' => [
                'transaction_id' => $transaction->id,
                'donor_name' => $this->isAnonymous ? 'Anonymous' : $this->donorName,
                'donation_type' => $this->donationType,
            ],
        ]);
    }

    public function handlePaymentSuccess(string $reference): void
    {
        $paystack = PaystackService::forBranch($this->branch);
        $result = $paystack->verifyTransaction($reference);

        if (! $result['success']) {
            $this->errorMessage = 'Payment verification failed. Please contact support.';

            return;
        }

        $transaction = PaymentTransaction::where('paystack_reference', $reference)->first();

        if (! $transaction) {
            $this->errorMessage = 'Transaction not found. Please contact support.';

            return;
        }

        $paystackData = $result['data'];

        // Update transaction
        $transaction->markAsSuccessful(
            (string) ($paystackData['id'] ?? ''),
            $paystackData['channel'] ?? null
        );

        // Create the donation record
        $metadata = $transaction->metadata ?? [];

        // Try to find a matching member by email
        $member = null;
        $donorEmail = $metadata['donor_email'] ?? null;
        if ($donorEmail) {
            $member = Member::where('email', $donorEmail)
                ->where('primary_branch_id', $this->branch->id)
                ->first();
        }

        $donation = Donation::create([
            'branch_id' => $this->branch->id,
            'member_id' => $member?->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'donation_type' => $metadata['donation_type'] ?? 'offering',
            'payment_method' => PaymentMethod::Paystack,
            'donation_date' => now()->toDateString(),
            'reference_number' => $reference,
            'donor_name' => $metadata['is_anonymous'] ? null : ($metadata['donor_name'] ?? null),
            'donor_email' => $donorEmail,
            'donor_phone' => $metadata['donor_phone'] ?? null,
            'is_anonymous' => $metadata['is_anonymous'] ?? false,
            'is_recurring' => $metadata['is_recurring'] ?? false,
            'recurring_interval' => $metadata['recurring_interval'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'paystack_customer_code' => $paystackData['customer']['customer_code'] ?? null,
        ]);

        // Link donation to transaction
        $transaction->update(['donation_id' => $donation->id]);

        $this->lastDonation = $donation;
        $this->showThankYou = true;
    }

    public function handlePaymentClosed(): void
    {
        // User closed the popup without completing payment
        $this->errorMessage = 'Payment was not completed. Please try again.';
    }

    public function giveAgain(): void
    {
        $this->reset([
            'amount', 'donationType', 'isAnonymous', 'isRecurring',
            'recurringInterval', 'notes', 'showThankYou', 'lastDonation', 'errorMessage',
        ]);
        $this->donationType = 'offering';
    }

    public function render()
    {
        return view('livewire.giving.public-giving-form');
    }
}
