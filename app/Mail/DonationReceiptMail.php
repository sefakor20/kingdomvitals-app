<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant\Donation;
use App\Services\DonationReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your Donation Receipt - :church', [
                'church' => $this->donation->branch->name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.donation-receipt',
            with: [
                'donation' => $this->donation,
                'branch' => $this->donation->branch,
                'donorName' => $this->donation->getDonorDisplayName(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdfContent = app(DonationReceiptService::class)->generatePdf($this->donation);

        return [
            Attachment::fromData(
                fn () => $pdfContent,
                "receipt-{$this->donation->getReceiptNumber()}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
