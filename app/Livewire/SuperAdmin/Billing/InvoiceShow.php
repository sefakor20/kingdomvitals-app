<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Billing;

use App\Enums\PlatformPaymentMethod;
use App\Models\PlatformInvoice;
use App\Models\SuperAdminActivityLog;
use App\Services\PlatformBillingService;
use App\Services\PlatformInvoicePdfService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceShow extends Component
{
    #[Locked]
    public string $invoiceId;

    public bool $showRecordPaymentModal = false;

    public bool $showCancelModal = false;

    public float $paymentAmount = 0;

    public string $paymentMethod = 'bank_transfer';

    public string $paymentNotes = '';

    public string $cancelReason = '';

    public function mount(PlatformInvoice $invoice): void
    {
        $this->invoiceId = $invoice->id;
        $this->paymentAmount = (float) $invoice->balance_due;
    }

    #[Computed]
    public function invoice(): PlatformInvoice
    {
        return PlatformInvoice::with(['tenant', 'subscriptionPlan', 'items', 'payments.tenant', 'reminders'])
            ->findOrFail($this->invoiceId);
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return collect(PlatformPaymentMethod::cases())
            ->mapWithKeys(fn (PlatformPaymentMethod $method) => [$method->value => $method->label()])
            ->toArray();
    }

    public function sendInvoice(): void
    {
        $invoice = $this->invoice;

        if (! $invoice->status->canBeSent()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'This invoice cannot be sent.',
            ]);

            return;
        }

        $invoice->markAsSent();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'send_invoice',
            description: "Sent invoice {$invoice->invoice_number}",
            tenant: $invoice->tenant,
            metadata: ['invoice_id' => $invoice->id],
        );

        unset($this->invoice);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Invoice sent successfully.',
        ]);
    }

    public function openRecordPaymentModal(): void
    {
        $this->paymentAmount = (float) $this->invoice->balance_due;
        $this->paymentMethod = 'bank_transfer';
        $this->paymentNotes = '';
        $this->showRecordPaymentModal = true;
    }

    public function recordPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:'.$this->invoice->balance_due,
            'paymentMethod' => 'required|in:'.implode(',', array_keys($this->paymentMethods)),
            'paymentNotes' => 'nullable|string|max:500',
        ]);

        $invoice = $this->invoice;
        $billingService = app(PlatformBillingService::class);

        $payment = $billingService->recordPayment($invoice, [
            'amount' => $this->paymentAmount,
            'payment_method' => PlatformPaymentMethod::from($this->paymentMethod),
            'notes' => $this->paymentNotes ?: null,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'record_payment',
            description: "Recorded payment of {$payment->currency} {$payment->amount} for invoice {$invoice->invoice_number}",
            tenant: $invoice->tenant,
            metadata: [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method->value,
            ],
        );

        $this->showRecordPaymentModal = false;
        unset($this->invoice);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Payment recorded successfully.',
        ]);
    }

    public function openCancelModal(): void
    {
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function cancelInvoice(): void
    {
        $this->validate([
            'cancelReason' => 'required|string|min:5|max:500',
        ]);

        $invoice = $this->invoice;

        if (! $invoice->status->canBeCancelled()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'This invoice cannot be cancelled.',
            ]);

            return;
        }

        $billingService = app(PlatformBillingService::class);
        $billingService->cancelInvoice($invoice, $this->cancelReason);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'cancel_invoice',
            description: "Cancelled invoice {$invoice->invoice_number}",
            tenant: $invoice->tenant,
            metadata: [
                'invoice_id' => $invoice->id,
                'reason' => $this->cancelReason,
            ],
        );

        $this->showCancelModal = false;
        unset($this->invoice);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Invoice cancelled.',
        ]);
    }

    public function downloadPdf(): StreamedResponse
    {
        $pdfService = app(PlatformInvoicePdfService::class);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'download_invoice_pdf',
            description: "Downloaded PDF for invoice {$this->invoice->invoice_number}",
            tenant: $this->invoice->tenant,
            metadata: ['invoice_id' => $this->invoice->id],
        );

        return $pdfService->download($this->invoice);
    }

    public function render(): View
    {
        return view('livewire.super-admin.billing.invoice-show')
            ->layout('components.layouts.superadmin.app');
    }
}
