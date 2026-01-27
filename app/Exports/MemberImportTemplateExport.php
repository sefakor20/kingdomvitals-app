<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MemberImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'first_name',
            'last_name',
            'middle_name',
            'maiden_name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'marital_status',
            'status',
            'employment_status',
            'profession',
            'address',
            'city',
            'state',
            'zip',
            'country',
            'hometown',
            'gps_address',
            'joined_at',
            'baptized_at',
            'confirmation_date',
            'notes',
            'previous_congregation',
        ];
    }

    public function array(): array
    {
        // Provide sample rows to demonstrate the expected format
        return [
            [
                'John',                     // first_name (required)
                'Doe',                      // last_name (required)
                'Michael',                  // middle_name
                '',                         // maiden_name
                'john.doe@example.com',     // email
                '0241234567',              // phone
                '1990-05-15',              // date_of_birth (YYYY-MM-DD)
                'male',                     // gender (male, female)
                'married',                  // marital_status (single, married, divorced, widowed)
                'active',                   // status (active, inactive, pending, deceased, transferred)
                'employed',                 // employment_status (employed, self_employed, unemployed, student, retired)
                'Software Engineer',        // profession
                '123 Main Street',          // address
                'Accra',                    // city
                'Greater Accra',            // state
                '00233',                    // zip
                'Ghana',                    // country
                'Kumasi',                   // hometown
                'GA-123-4567',             // gps_address
                '2020-01-01',              // joined_at (YYYY-MM-DD)
                '2015-06-15',              // baptized_at (YYYY-MM-DD)
                '2010-04-10',              // confirmation_date (YYYY-MM-DD)
                'Active member',            // notes
                'Grace Baptist Church',     // previous_congregation
            ],
            [
                'Jane',                     // first_name (required)
                'Smith',                    // last_name (required)
                '',                         // middle_name
                'Johnson',                  // maiden_name (for married women)
                'jane.smith@example.com',   // email
                '0551234567',              // phone
                '1985-08-22',              // date_of_birth
                'female',                   // gender
                'married',                  // marital_status
                'active',                   // status
                'self_employed',            // employment_status (note: use underscore)
                'Business Owner',           // profession
                '456 Oak Avenue',           // address
                'Kumasi',                   // city
                'Ashanti',                  // state
                '',                         // zip
                'Ghana',                    // country
                'Accra',                    // hometown
                '',                         // gps_address
                '2019-03-15',              // joined_at
                '2010-12-25',              // baptized_at
                '',                         // confirmation_date
                '',                         // notes
                '',                         // previous_congregation
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
