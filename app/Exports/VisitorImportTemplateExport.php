<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitorImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'email',
            'phone',
            'visit_date',
            'status',
            'how_did_you_hear',
            'notes',
        ];
    }

    public function array(): array
    {
        // Provide sample rows to demonstrate the expected format
        return [
            [
                'John',                     // first_name (required)
                'Doe',                      // last_name (required)
                'john.doe@example.com',     // email
                '0241234567',               // phone
                '2024-01-15',               // visit_date (required, YYYY-MM-DD)
                'new',                      // status (new, followed_up, returning, converted, not_interested)
                'Friend or family',         // how_did_you_hear
                'First-time visitor',       // notes
            ],
            [
                'Jane',                     // first_name (required)
                'Smith',                    // last_name (required)
                'jane.smith@example.com',   // email
                '0551234567',               // phone
                '2024-01-22',               // visit_date (required)
                'followed_up',              // status
                'Social media',             // how_did_you_hear
                'Interested in joining',    // notes
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}
