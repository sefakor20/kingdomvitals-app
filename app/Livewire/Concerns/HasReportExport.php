<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\ReportExportService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HasReportExport
{
    /**
     * Export data to CSV format.
     */
    public function exportToCsv(Collection $data, array $headers, string $filename): StreamedResponse
    {
        return app(ReportExportService::class)->exportToCsv($data, $headers, $filename);
    }

    /**
     * Export data to Excel format.
     */
    public function exportToExcel(Collection $data, array $headers, string $filename): StreamedResponse
    {
        return app(ReportExportService::class)->exportToExcel($data, $headers, $filename);
    }

    /**
     * Export data to PDF format.
     */
    public function exportToPdf(string $view, array $data, string $filename): StreamedResponse
    {
        return app(ReportExportService::class)->exportToPdf($view, $data, $filename);
    }

    /**
     * Generate a report filename with branch and date.
     */
    protected function generateFilename(string $reportName, string $extension = 'csv'): string
    {
        $branchSlug = property_exists($this, 'branch') ? $this->branch->slug : 'report';
        $date = now()->format('Y-m-d');

        return "{$reportName}-{$branchSlug}-{$date}.{$extension}";
    }
}
