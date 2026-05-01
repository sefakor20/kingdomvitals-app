<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\Currency;
use App\Enums\PlatformPaymentMethod;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\SystemSetting;
use App\Services\PlatformBillingService;
use App\Services\PlatformPaystackService;
use App\Services\TenantUpgradeService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PaymentHistory extends Component
{
    public ?string $payingInvoiceId = null;

    public ?string $errorMessage = null;

    public bool $isProcessing = false;

    /**
     * Get all invoices for the current tenant.
     *
     * @return Collection<int, PlatformInvoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        $tenantId = tenant()?->id;

        if (! $tenantId) {
            return collect();
        }

        return PlatformInvoice::forTenant($tenantId)
            ->with('subscriptionPlan')
            ->orderByDesc('issue_date')
            ->get();
    }

    /**
     * Get all payments for the current tenant.
     *
     * @return Collection<int, PlatformPayment>
     */
    #[Computed]
    public function payments(): Collection
    {
        $tenantId = tenant()?->id;

        if (! $tenantId) {
            return collect();
        }

        return PlatformPayment::forTenant($tenantId)
            ->with('invoice')
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check if there is any billing history.
     */
    #[Computed]
    public function hasBillingHistory(): bool
    {
        if ($this->invoices->isNotEmpty()) {
            return true;
        }

        return (bool) $this->payments->isNotEmpty();
    }

    #[Computed]
    public function paystackPublicKey(): string
    {
        return app(PlatformPaystackService::class)->getPublicKey();
    }

    #[Computed]
    public function paystackConfigured(): bool
    {
        return app(PlatformPaystackService::class)->isConfigured();
    }

    #[Computed]
    public function currency(): Currency
    {
        return Currency::fromString(SystemSetting::get('base_currency', 'GHS'));
    }

    /**
     * Initiate payment for a specific invoice via Paystack.
     */
    public function initiateInvoicePayment(string $invoiceId): void
    {
        $this->errorMessage = null;
        $tenantId = tenant()?->id;

        $invoice = PlatformInvoice::where('id', $invoiceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $invoice || ! $invoice->status->canReceivePayment()) {
            $this->errorMessage = __('This invoice cannot be paid at this time.');

            return;
        }

        if (! $this->paystackConfigured) {
            $this->errorMessage = __('Payment system is not configured. Please contact support.');

            return;
        }

        $this->isProcessing = true;
        $this->payingInvoiceId = $invoiceId;

        $user = auth()->user();
        $tenant = tenant();
        $email = $user->email ?? $tenant->contact_email ?? '';

        $paystackService = app(PlatformPaystackService::class);

        $result = $paystackService->initializeTransaction([
            'email' => $email,
            'amount' => PlatformPaystackService::toKobo((float) $invoice->balance_due),
            'metadata' => [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenantId,
                'type' => $invoice->metadata['type'] ?? 'invoice_payment',
            ],
        ]);

        if (! $result['success']) {
            $this->isProcessing = false;
            $this->payingInvoiceId = null;
            $this->errorMessage = $result['error'] ?? __('Failed to initialize payment. Please try again.');

            return;
        }

        $invoice->update([
            'metadata' => array_merge($invoice->metadata ?? [], [
                'paystack_reference' => $result['reference'],
            ]),
        ]);

        $this->dispatch('open-paystack', [
            'key' => $this->paystackPublicKey,
            'email' => $email,
            'amount' => PlatformPaystackService::toKobo((float) $invoice->balance_due),
            'currency' => $this->currency->code(),
            'reference' => $result['reference'],
            'metadata' => [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenantId,
            ],
        ]);
    }

    /**
     * Handle successful Paystack payment callback.
     */
    public function handlePaymentSuccess(string $reference): void
    {
        $invoice = PlatformInvoice::whereJsonContains('metadata->paystack_reference', $reference)
            ->where('tenant_id', tenant()?->id)
            ->first();

        if (! $invoice) {
            $this->isProcessing = false;
            $this->errorMessage = __('Invoice not found. Please contact support.');

            return;
        }

        // For plan upgrades, delegate to TenantUpgradeService which handles subscription updates
        if (($invoice->metadata['type'] ?? '') === 'plan_upgrade') {
            $result = app(TenantUpgradeService::class)->completeUpgrade($reference);

            if (! $result['success']) {
                $this->isProcessing = false;
                $this->errorMessage = $result['error'] ?? __('Payment could not be confirmed. Please contact support.');

                return;
            }
        } else {
            $paystackService = app(PlatformPaystackService::class);
            $verify = $paystackService->verifyTransaction($reference);

            if (! $verify['success']) {
                $this->isProcessing = false;
                $this->errorMessage = __('Payment verification failed. Please contact support.');

                return;
            }

            app(PlatformBillingService::class)->recordPayment($invoice, [
                'amount' => (float) $invoice->balance_due,
                'payment_method' => PlatformPaymentMethod::Paystack,
                'paystack_reference' => $reference,
                'notes' => 'Self-service invoice payment',
            ]);
        }

        $this->isProcessing = false;
        $this->payingInvoiceId = null;
        unset($this->invoices, $this->payments);

        session()->flash('success', __('Payment successful! Your invoice has been marked as paid.'));

        // If this clears the last past-due invoice, drop the user out of the paywall.
        $tenantId = tenant()?->id;
        if ($tenantId && ! PlatformInvoice::forTenant($tenantId)->pastDue()->exists()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    /**
     * Handle Paystack popup closed without completing payment.
     */
    public function handlePaymentClosed(): void
    {
        $this->isProcessing = false;
        $this->payingInvoiceId = null;
        $this->errorMessage = __('Payment was cancelled. You can try again when ready.');
    }

    public function render(): Factory|View
    {
        return view('livewire.settings.payment-history');
    }
}
