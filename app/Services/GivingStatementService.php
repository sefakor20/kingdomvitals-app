<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GivingStatementService
{
    /**
     * Generate a giving statement PDF for a member for a specific year.
     */
    public function generatePdf(Member $member, int $year): string
    {
        $member->loadMissing('primaryBranch');
        $branch = $member->primaryBranch;

        $donations = $member->donations()
            ->whereYear('donation_date', $year)
            ->orderBy('donation_date')
            ->get();

        $currency = tenant()->getCurrency();

        $pdf = Pdf::loadView('pdf.giving-statement', [
            'member' => $member,
            'branch' => $branch,
            'donations' => $donations,
            'year' => $year,
            'total' => $donations->sum('amount'),
            'currency' => $currency,
        ]);

        return $pdf->output();
    }

    /**
     * Download a giving statement as PDF.
     */
    public function downloadStatement(Member $member, int $year): StreamedResponse
    {
        $pdfContent = $this->generatePdf($member, $year);
        $filename = "giving-statement-{$year}-{$member->membership_number}.pdf";

        return response()->streamDownload(
            fn () => print ($pdfContent),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Get monthly totals for a member for a specific year.
     *
     * @return Collection<int, float>
     */
    public function getMonthlyTotals(Member $member, int $year): Collection
    {
        $donations = $member->donations()
            ->whereYear('donation_date', $year)
            ->get();

        $monthlyTotals = collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => 0.0]);

        foreach ($donations as $donation) {
            $month = (int) $donation->donation_date->format('n');
            $monthlyTotals[$month] += (float) $donation->amount;
        }

        return $monthlyTotals;
    }
}
