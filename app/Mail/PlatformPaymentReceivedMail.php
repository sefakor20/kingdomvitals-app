<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PlatformPayment;
use App\Services\PlatformInvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformPaymentReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlatformPayment $payment,
        public bool $attachInvoice = true
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Received - Invoice {$this->payment->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform.payment-received',
            with: [
                'payment' => $this->payment,
                'invoice' => $this->payment->invoice,
                'tenant' => $this->payment->invoice->tenant,
                'invoiceUrl' => route('superadmin.billing.invoices.show', $this->payment->invoice),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->attachInvoice) {
            return [];
        }

        $pdfContent = app(PlatformInvoicePdfService::class)->generate($this->payment->invoice);

        return [
            Attachment::fromData(
                fn () => $pdfContent,
                "invoice-{$this->payment->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
