<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\DonationReceiptMail;
use App\Models\Tenant\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DonationReceiptService
{
    /**
     * Generate a unique receipt number for a donation.
     */
    public function generateReceiptNumber(Donation $donation): string
    {
        $branch = $donation->branch;
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branch->name), 0, 2));
        $yearMonth = $donation->donation_date->format('Ym');

        $lastReceipt = Donation::where('branch_id', $branch->id)
            ->whereNotNull('receipt_number')
            ->where('receipt_number', 'like', "REC-{$prefix}-{$yearMonth}-%")
            ->orderByDesc('receipt_number')
            ->first();

        $sequence = 1;
        if ($lastReceipt) {
            $parts = explode('-', $lastReceipt->receipt_number);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('REC-%s-%s-%05d', $prefix, $yearMonth, $sequence);
    }

    /**
     * Generate PDF content for a donation receipt.
     */
    public function generatePdf(Donation $donation): string
    {
        $donation->loadMissing(['branch', 'member', 'service']);

        if (! $donation->receipt_number) {
            $donation->receipt_number = $this->generateReceiptNumber($donation);
            $donation->save();
        }

        $pdf = Pdf::loadView('receipts.donation-receipt', [
            'donation' => $donation,
            'branch' => $donation->branch,
        ]);

        return $pdf->output();
    }

    /**
     * Download a single donation receipt as PDF.
     */
    public function downloadReceipt(Donation $donation): StreamedResponse
    {
        $pdfContent = $this->generatePdf($donation);
        $filename = "receipt-{$donation->getReceiptNumber()}.pdf";

        return response()->streamDownload(
            fn () => print ($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Email a receipt to the donor.
     */
    public function emailReceipt(Donation $donation): bool
    {
        if (! $donation->canSendReceipt()) {
            return false;
        }

        $email = $donation->getDonorEmail();

        Mail::to($email)->queue(new DonationReceiptMail($donation));

        $donation->update(['receipt_sent_at' => now()]);

        return true;
    }

    /**
     * Download multiple receipts as a ZIP file.
     */
    public function bulkDownloadReceipts(Collection $donations): StreamedResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'receipts');
        $zip = new ZipArchive;
        $zip->open($tempFile, ZipArchive::CREATE);

        foreach ($donations as $donation) {
            $pdfContent = $this->generatePdf($donation);
            $filename = "receipt-{$donation->getReceiptNumber()}.pdf";
            $zip->addFromString($filename, $pdfContent);
        }

        $zip->close();

        $filename = 'donation-receipts-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(
            function () use ($tempFile) {
                readfile($tempFile);
                unlink($tempFile);
            },
            $filename,
            ['Content-Type' => 'application/zip']
        );
    }

    /**
     * Send receipts to multiple donors via email.
     *
     * @return array{sent: int, skipped: int}
     */
    public function bulkEmailReceipts(Collection $donations): array
    {
        $sent = 0;
        $skipped = 0;

        foreach ($donations as $donation) {
            if ($this->emailReceipt($donation)) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }
}
