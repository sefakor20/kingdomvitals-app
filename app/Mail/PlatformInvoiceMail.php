<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PlatformInvoice;
use App\Services\PlatformInvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlatformInvoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.billing.from.address'),
                config('mail.billing.from.name'),
            ),
            replyTo: [new Address(
                config('mail.billing.reply_to.address'),
                config('mail.billing.reply_to.name'),
            )],
            subject: "Invoice {$this->invoice->invoice_number} - Kingdom Vitals",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.platform.invoice-sent',
            with: [
                'invoice' => $this->invoice,
                'tenant' => $this->invoice->tenant,
                'invoiceUrl' => route('superadmin.billing.invoices.show', $this->invoice),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdfContent = app(PlatformInvoicePdfService::class)->generate($this->invoice);

        return [
            Attachment::fromData(
                fn () => $pdfContent,
                "invoice-{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
