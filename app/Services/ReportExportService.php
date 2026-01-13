<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export data to CSV format.
     */
    public function exportToCsv(Collection $data, array $headers, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data, $headers): void {
            $handle = fopen('php://output', 'w');

            // Write headers
            fputcsv($handle, $headers);

            // Write data rows
            foreach ($data as $row) {
                if (is_array($row)) {
                    fputcsv($handle, $row);
                } elseif (is_object($row)) {
                    fputcsv($handle, array_values((array) $row));
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export data to Excel format.
     */
    public function exportToExcel(Collection $data, array $headers, string $filename): StreamedResponse
    {
        $export = new \App\Exports\GenericReportExport($data, $headers);

        return Excel::download($export, $filename);
    }

    /**
     * Export data to PDF format.
     */
    public function exportToPdf(string $view, array $data, string $filename): StreamedResponse
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Export data to PDF in landscape format.
     */
    public function exportToPdfLandscape(string $view, array $data, string $filename): StreamedResponse
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
