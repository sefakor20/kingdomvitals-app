<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformInvoicePdfService
{
    /**
     * Generate PDF content for an invoice.
     */
    public function generate(PlatformInvoice $invoice): string
    {
        $invoice->loadMissing(['tenant', 'subscriptionPlan', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.platform-invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'plan' => $invoice->subscriptionPlan,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
        ]);

        return $pdf->output();
    }

    /**
     * Download an invoice as PDF.
     */
    public function download(PlatformInvoice $invoice): StreamedResponse
    {
        $pdfContent = $this->generate($invoice);
        $filename = "invoice-{$invoice->invoice_number}.pdf";

        return response()->streamDownload(
            fn () => print ($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Store the invoice PDF and return the path.
     */
    public function store(PlatformInvoice $invoice): string
    {
        $pdfContent = $this->generate($invoice);
        $path = "invoices/{$invoice->tenant_id}/{$invoice->invoice_number}.pdf";

        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Preview the invoice in the browser.
     */
    public function preview(PlatformInvoice $invoice): StreamedResponse
    {
        $pdfContent = $this->generate($invoice);

        return response()->streamDownload(
            fn () => print ($pdfContent),
            "invoice-{$invoice->invoice_number}.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice-'.$invoice->invoice_number.'.pdf"',
            ]
        );
    }
}
