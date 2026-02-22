<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\EventRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventTicketPdfService
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {}

    /**
     * Generate PDF content for an event ticket.
     */
    public function generatePdf(EventRegistration $registration): string
    {
        $registration->loadMissing(['event', 'branch']);

        $pdf = Pdf::loadView('pdf.event-ticket', [
            'registration' => $registration,
            'event' => $registration->event,
            'branch' => $registration->branch,
            'qrCode' => $this->qrCodeService->generateEventTicketQrCodeBase64($registration, 180),
        ]);

        return $pdf->output();
    }

    /**
     * Download an event ticket as PDF.
     */
    public function downloadTicket(EventRegistration $registration): StreamedResponse
    {
        $pdfContent = $this->generatePdf($registration);
        $filename = "ticket-{$registration->ticket_number}.pdf";

        return response()->streamDownload(
            fn () => print ($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
