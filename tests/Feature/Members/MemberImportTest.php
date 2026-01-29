<?php

declare(strict_types=1);

use App\Enums\BranchRole;
use App\Imports\MemberImport;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\UserBranchAccess;
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
function createCsvFile(string $content): string
{
    $tempPath = sys_get_temp_dir().'/test_import_'.uniqid().'.csv';
    file_put_contents($tempPath, $content);

    return $tempPath;
}

// ============================================
// IMPORT CLASS TESTS
// ============================================

it('imports valid csv data correctly', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,email,phone,gender,status\n";
    $csvContent .= "John,Doe,john@example.com,0241234567,male,active\n";
    $csvContent .= "Jane,Smith,jane@example.com,0551234567,female,active\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(2);
        expect(Member::count())->toBe(2);

        $john = Member::where('email', 'john@example.com')->first();
        expect($john)->not->toBeNull()
            ->and($john->first_name)->toBe('John')
            ->and($john->last_name)->toBe('Doe')
            ->and($john->gender->value)->toBe('male')
            ->and($john->status->value)->toBe('active');
    } finally {
        @unlink($tempPath);
    }
});

it('imports with only required fields', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name\n";
    $csvContent .= "John,Doe\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $member = Member::first();
        expect($member->first_name)->toBe('John')
            ->and($member->last_name)->toBe('Doe')
            ->and($member->status->value)->toBe('active'); // Default status
    } finally {
        @unlink($tempPath);
    }
});

it('skips rows with duplicate email', function (): void {
    // Create existing member with email
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'email' => 'existing@example.com',
    ]);

    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,email\n";
    $csvContent .= "John,Doe,existing@example.com\n";
    $csvContent .= "Jane,Smith,new@example.com\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(1);
        expect(Member::count())->toBe(2); // 1 existing + 1 new
    } finally {
        @unlink($tempPath);
    }
});

it('skips rows with duplicate phone', function (): void {
    // Create existing member with phone
    Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
        'phone' => '0241234567',
    ]);

    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,phone\n";
    $csvContent .= "John,Doe,0241234567\n";
    $csvContent .= "Jane,Smith,0551234567\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);
        expect($import->getSkippedDuplicates())->toHaveCount(1);
    } finally {
        @unlink($tempPath);
    }
});

it('collects validation failures for missing required fields', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,email\n";
    $csvContent .= ",Doe,john@example.com\n"; // Missing first_name
    $csvContent .= "Jane,,jane@example.com\n"; // Missing last_name

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->failures())->not->toBeEmpty();
        expect($import->getImportedCount())->toBe(0);
    } finally {
        @unlink($tempPath);
    }
});

it('handles enum values correctly', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,gender,marital_status,status,employment_status\n";
    $csvContent .= "John,Doe,male,married,active,self_employed\n";
    $csvContent .= "Jane,Smith,female,single,pending,student\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(2);

        $john = Member::where('first_name', 'John')->first();
        expect($john->gender->value)->toBe('male')
            ->and($john->marital_status->value)->toBe('married')
            ->and($john->status->value)->toBe('active')
            ->and($john->employment_status->value)->toBe('self_employed');

        $jane = Member::where('first_name', 'Jane')->first();
        expect($jane->gender->value)->toBe('female')
            ->and($jane->marital_status->value)->toBe('single')
            ->and($jane->status->value)->toBe('pending')
            ->and($jane->employment_status->value)->toBe('student');
    } finally {
        @unlink($tempPath);
    }
});

it('assigns correct branch id to imported members', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name\n";
    $csvContent .= "John,Doe\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        $member = Member::first();
        expect($member->primary_branch_id)->toBe($this->branch->id);
    } finally {
        @unlink($tempPath);
    }
});

it('handles empty optional fields gracefully', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,email,phone,gender,address\n";
    $csvContent .= "John,Doe,,,,,\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $member = Member::first();
        expect($member->email)->toBeNull()
            ->and($member->phone)->toBeNull()
            ->and($member->gender)->toBeNull()
            ->and($member->address)->toBeNull();
    } finally {
        @unlink($tempPath);
    }
});

it('skips empty rows', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name\n";
    $csvContent .= "John,Doe\n";
    $csvContent .= ",,\n"; // Empty row
    $csvContent .= "Jane,Smith\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(2);
        expect(Member::count())->toBe(2);
    } finally {
        @unlink($tempPath);
    }
});

it('imports members with date fields', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,date_of_birth,joined_at,baptized_at\n";
    $csvContent .= "John,Doe,1990-05-15,2020-01-01,2015-06-15\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $member = Member::first();
        expect($member->date_of_birth->format('Y-m-d'))->toBe('1990-05-15')
            ->and($member->joined_at->format('Y-m-d'))->toBe('2020-01-01')
            ->and($member->baptized_at->format('Y-m-d'))->toBe('2015-06-15');
    } finally {
        @unlink($tempPath);
    }
});

it('imports members with all fields', function (): void {
    $import = new MemberImport($this->branch->id);

    $csvContent = "first_name,last_name,middle_name,maiden_name,email,phone,date_of_birth,gender,marital_status,status,employment_status,profession,address,city,state,zip,country,hometown,gps_address,joined_at,baptized_at,confirmation_date,notes,previous_congregation\n";
    $csvContent .= "John,Doe,Michael,,john@test.com,0241234567,1990-05-15,male,married,active,employed,Engineer,123 Main St,Accra,Greater Accra,00233,Ghana,Kumasi,GA-123-4567,2020-01-01,2015-06-15,2010-04-10,Active member,Grace Church\n";

    $tempPath = createCsvFile($csvContent);

    try {
        Excel::import($import, $tempPath, null, ExcelType::CSV);

        expect($import->getImportedCount())->toBe(1);

        $member = Member::first();
        expect($member->first_name)->toBe('John')
            ->and($member->last_name)->toBe('Doe')
            ->and($member->middle_name)->toBe('Michael')
            ->and($member->email)->toBe('john@test.com')
            ->and($member->phone)->toBe('0241234567')
            ->and($member->profession)->toBe('Engineer')
            ->and($member->address)->toBe('123 Main St')
            ->and($member->city)->toBe('Accra')
            ->and($member->country)->toBe('Ghana')
            ->and($member->hometown)->toBe('Kumasi')
            ->and($member->notes)->toBe('Active member')
            ->and($member->previous_congregation)->toBe('Grace Church');
    } finally {
        @unlink($tempPath);
    }
});
