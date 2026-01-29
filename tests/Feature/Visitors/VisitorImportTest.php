<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Enums\VisitorStatus;
use App\Imports\VisitorImport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();

    $this->admin = User::factory()->create();
    UserBranchAccess::factory()->create([
        'user_id' => $this->admin->id,
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin,
    ]);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// Helper function to create a CSV file for testing
function createVisitorCsvFile(string $content): string
{
    $tempPath = sys_get_temp_dir().'/test_visitor_import_'.uniqid().'.csv';
    file_put_contents($tempPath, $content);

    return $tempPath;
}

// ============================================
// IMPORT CLASS TESTS
// ============================================

it('imports valid csv data correctly', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,email,phone,visit_date,status,how_did_you_hear,notes\n";
    $csvContent .= "John,Doe,john@example.com,0241234567,2024-01-15,new,Friend or family,First-time visitor\n";
    $csvContent .= "Jane,Smith,jane@example.com,0551234567,2024-01-22,followed_up,Social media,Interested in joining\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(2);
        expect(Visitor::count())->toBe(2);

        $john = Visitor::where('email', 'john@example.com')->first();
        expect($john)->not->toBeNull()
            ->and($john->first_name)->toBe('John')
            ->and($john->last_name)->toBe('Doe')
            ->and($john->status)->toBe(VisitorStatus::New)
            ->and($john->how_did_you_hear)->toBe('Friend or family');

        $jane = Visitor::where('email', 'jane@example.com')->first();
        expect($jane)->not->toBeNull()
            ->and($jane->status)->toBe(VisitorStatus::FollowedUp);
    } finally {
        @unlink($tempPath);
    }
});

it('imports with only required fields', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date\n";
    $csvContent .= "John,Doe,2024-01-15\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $visitor = Visitor::first();
        expect($visitor->first_name)->toBe('John')
            ->and($visitor->last_name)->toBe('Doe')
            ->and($visitor->status)->toBe(VisitorStatus::New); // Default status
    } finally {
        @unlink($tempPath);
    }
});

it('skips rows with duplicate email', function (): void {
    // Create existing visitor with email
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => 'existing@example.com',
    ]);

    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email\n";
    $csvContent .= "John,Doe,2024-01-15,existing@example.com\n";
    $csvContent .= "Jane,Smith,2024-01-16,new@example.com\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(1);
        expect(Visitor::count())->toBe(2); // 1 existing + 1 new
    } finally {
        @unlink($tempPath);
    }
});

it('skips rows with duplicate phone', function (): void {
    // Create existing visitor with phone
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'phone' => '0241234567',
    ]);

    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,phone\n";
    $csvContent .= "John,Doe,2024-01-15,0241234567\n";
    $csvContent .= "Jane,Smith,2024-01-16,0551234567\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(1);
    } finally {
        @unlink($tempPath);
    }
});

it('collects validation failures for missing required fields', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email\n";
    $csvContent .= ",Doe,2024-01-15,john@example.com\n"; // Missing first_name
    $csvContent .= "Jane,,2024-01-16,jane@example.com\n"; // Missing last_name

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->failures())->not->toBeEmpty();
        expect($import->getImportedCount())->toBe(0);
    } finally {
        @unlink($tempPath);
    }
});

it('collects validation failures for missing visit date', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email\n";
    $csvContent .= "John,Doe,,john@example.com\n"; // Missing visit_date

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->failures())->not->toBeEmpty();
        expect($import->getImportedCount())->toBe(0);
    } finally {
        @unlink($tempPath);
    }
});

it('handles status enum values correctly', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,status\n";
    $csvContent .= "John,Doe,2024-01-15,new\n";
    $csvContent .= "Jane,Smith,2024-01-16,followed_up\n";
    $csvContent .= "Bob,Johnson,2024-01-17,returning\n";
    $csvContent .= "Alice,Williams,2024-01-18,converted\n";
    $csvContent .= "Charlie,Brown,2024-01-19,not_interested\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(5);

        expect(Visitor::where('first_name', 'John')->first()->status)->toBe(VisitorStatus::New);
        expect(Visitor::where('first_name', 'Jane')->first()->status)->toBe(VisitorStatus::FollowedUp);
        expect(Visitor::where('first_name', 'Bob')->first()->status)->toBe(VisitorStatus::Returning);
        expect(Visitor::where('first_name', 'Alice')->first()->status)->toBe(VisitorStatus::Converted);
        expect(Visitor::where('first_name', 'Charlie')->first()->status)->toBe(VisitorStatus::NotInterested);
    } finally {
        @unlink($tempPath);
    }
});

it('rejects invalid status values', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,status\n";
    $csvContent .= "John,Doe,2024-01-15,invalid_status\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->failures())->not->toBeEmpty();
        expect($import->getImportedCount())->toBe(0);
    } finally {
        @unlink($tempPath);
    }
});

it('assigns correct branch id to imported visitors', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date\n";
    $csvContent .= "John,Doe,2024-01-15\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        $visitor = Visitor::first();
        expect($visitor->branch_id)->toBe($this->branch->id);
    } finally {
        @unlink($tempPath);
    }
});

it('handles empty optional fields gracefully', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email,phone,how_did_you_hear,notes\n";
    $csvContent .= "John,Doe,2024-01-15,,,,\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $visitor = Visitor::first();
        expect($visitor->email)->toBeNull()
            ->and($visitor->phone)->toBeNull()
            ->and($visitor->how_did_you_hear)->toBeNull()
            ->and($visitor->notes)->toBeNull();
    } finally {
        @unlink($tempPath);
    }
});

it('skips empty rows', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date\n";
    $csvContent .= "John,Doe,2024-01-15\n";
    $csvContent .= ",,,\n"; // Empty row
    $csvContent .= "Jane,Smith,2024-01-16\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(2);
        expect(Visitor::count())->toBe(2);
    } finally {
        @unlink($tempPath);
    }
});

it('imports visitors with various date formats', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date\n";
    $csvContent .= "John,Doe,2024-01-15\n";
    $csvContent .= "Jane,Smith,2024/02/20\n";
    $csvContent .= "Bob,Johnson,March 5 2024\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(3);

        expect(Visitor::where('first_name', 'John')->first()->visit_date->format('Y-m-d'))->toBe('2024-01-15');
        expect(Visitor::where('first_name', 'Jane')->first()->visit_date->format('Y-m-d'))->toBe('2024-02-20');
        expect(Visitor::where('first_name', 'Bob')->first()->visit_date->format('Y-m-d'))->toBe('2024-03-05');
    } finally {
        @unlink($tempPath);
    }
});

it('imports visitors with all fields', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,email,phone,visit_date,status,how_did_you_hear,notes\n";
    $csvContent .= "John,Doe,john@test.com,0241234567,2024-01-15,new,Friend or family,First-time visitor from downtown\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $visitor = Visitor::first();
        expect($visitor->first_name)->toBe('John')
            ->and($visitor->last_name)->toBe('Doe')
            ->and($visitor->email)->toBe('john@test.com')
            ->and($visitor->phone)->toBe('0241234567')
            ->and($visitor->visit_date->format('Y-m-d'))->toBe('2024-01-15')
            ->and($visitor->status)->toBe(VisitorStatus::New)
            ->and($visitor->how_did_you_hear)->toBe('Friend or family')
            ->and($visitor->notes)->toBe('First-time visitor from downtown');
    } finally {
        @unlink($tempPath);
    }
});

it('collects validation failure for invalid email', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email\n";
    $csvContent .= "John,Doe,2024-01-15,not-an-email\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->failures())->not->toBeEmpty();
        expect($import->getImportedCount())->toBe(0);
    } finally {
        @unlink($tempPath);
    }
});

it('defaults to new status when status is empty', function (): void {
    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,status\n";
    $csvContent .= "John,Doe,2024-01-15,\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $visitor = Visitor::first();
        expect($visitor->status)->toBe(VisitorStatus::New);
    } finally {
        @unlink($tempPath);
    }
});

it('does not detect duplicate when visitor has different branch', function (): void {
    $otherBranch = Branch::factory()->create();

    // Create visitor in different branch with same email
    Visitor::factory()->create([
        'branch_id' => $otherBranch->id,
        'email' => 'john@example.com',
    ]);

    $import = new VisitorImport($this->branch->id);

    $csvContent = "first_name,last_name,visit_date,email\n";
    $csvContent .= "John,Doe,2024-01-15,john@example.com\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(0);
    } finally {
        @unlink($tempPath);
    }
});

it('does not detect duplicate when neither email nor phone provided', function (): void {
    // Create existing visitor without email and phone
    Visitor::factory()->create([
        'branch_id' => $this->branch->id,
        'email' => null,
        'phone' => null,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $import = new VisitorImport($this->branch->id);

    // Import visitor without email and phone - should not be detected as duplicate
    $csvContent = "first_name,last_name,visit_date\n";
    $csvContent .= "John,Doe,2024-01-15\n";

    $tempPath = createVisitorCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(0);
    } finally {
        @unlink($tempPath);
    }
});
