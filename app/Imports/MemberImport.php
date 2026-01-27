<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MemberImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable;
    use SkipsFailures;

    protected int $importedCount = 0;

    protected array $skippedDuplicates = [];

    public function __construct(
        protected string $branchId
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Skip if row is empty or only has whitespace
            if ($this->isEmptyRow($row)) {
                continue;
            }

            // Check for duplicates by email or phone
            if ($this->isDuplicate($row)) {
                $this->skippedDuplicates[] = [
                    'row' => $index + 2, // +2 for header row and 0-index
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                ];

                continue;
            }

            Member::create($this->mapRowToMember($row));
            $this->importedCount++;
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'maiden_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,'],
            'marital_status' => ['nullable', 'string', 'in:single,married,divorced,widowed,'],
            'status' => ['nullable', 'string', 'in:active,inactive,pending,deceased,transferred,'],
            'employment_status' => ['nullable', 'string', 'in:employed,self_employed,unemployed,student,retired,'],
            'profession' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'hometown' => ['nullable', 'string', 'max:100'],
            'gps_address' => ['nullable', 'string', 'max:100'],
            'joined_at' => ['nullable', 'date'],
            'baptized_at' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'previous_congregation' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.email' => 'Email must be a valid email address.',
            'gender.in' => 'Gender must be male or female.',
            'marital_status.in' => 'Marital status must be single, married, divorced, or widowed.',
            'status.in' => 'Status must be active, inactive, pending, deceased, or transferred.',
            'employment_status.in' => 'Employment status must be employed, self_employed, unemployed, student, or retired.',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedDuplicates(): array
    {
        return $this->skippedDuplicates;
    }

    protected function isEmptyRow(Collection $row): bool
    {
        return $row->filter(fn ($value): bool => ! empty($value) && trim((string) $value) !== '')->isEmpty();
    }

    protected function isDuplicate(Collection $row): bool
    {
        $email = $this->normalizeValue($row['email'] ?? null);
        $phone = $this->normalizeValue($row['phone'] ?? null);

        if (! $email && ! $phone) {
            return false;
        }

        $query = Member::query();

        if ($email) {
            $query->where('email', $email);
        }

        if ($phone) {
            if ($email) {
                $query->orWhere('phone', $phone);
            } else {
                $query->where('phone', $phone);
            }
        }

        return $query->exists();
    }

    protected function mapRowToMember(Collection $row): array
    {
        return [
            'primary_branch_id' => $this->branchId,
            'first_name' => $this->normalizeValue($row['first_name']),
            'last_name' => $this->normalizeValue($row['last_name']),
            'middle_name' => $this->normalizeValue($row['middle_name'] ?? null),
            'maiden_name' => $this->normalizeValue($row['maiden_name'] ?? null),
            'email' => $this->normalizeValue($row['email'] ?? null),
            'phone' => $this->normalizeValue($row['phone'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'gender' => $this->parseEnum($row['gender'] ?? null, Gender::class),
            'marital_status' => $this->parseEnum($row['marital_status'] ?? null, MaritalStatus::class),
            'status' => $this->parseEnum($row['status'] ?? null, MembershipStatus::class) ?? MembershipStatus::Active,
            'employment_status' => $this->parseEnum($row['employment_status'] ?? null, EmploymentStatus::class),
            'profession' => $this->normalizeValue($row['profession'] ?? null),
            'address' => $this->normalizeValue($row['address'] ?? null),
            'city' => $this->normalizeValue($row['city'] ?? null),
            'state' => $this->normalizeValue($row['state'] ?? null),
            'zip' => $this->normalizeValue($row['zip'] ?? null),
            'country' => $this->normalizeValue($row['country'] ?? null),
            'hometown' => $this->normalizeValue($row['hometown'] ?? null),
            'gps_address' => $this->normalizeValue($row['gps_address'] ?? null),
            'joined_at' => $this->parseDate($row['joined_at'] ?? null),
            'baptized_at' => $this->parseDate($row['baptized_at'] ?? null),
            'confirmation_date' => $this->parseDate($row['confirmation_date'] ?? null),
            'notes' => $this->normalizeValue($row['notes'] ?? null),
            'previous_congregation' => $this->normalizeValue($row['previous_congregation'] ?? null),
        ];
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Handle Excel numeric date format
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
        }

        // Try to parse as date string
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    protected function parseEnum(mixed $value, string $enumClass): mixed
    {
        $normalized = $this->normalizeValue($value);

        if (! $normalized) {
            return null;
        }

        // Convert to lowercase for matching
        $normalized = strtolower($normalized);

        // Handle special case for self_employed (might come as "self-employed" or "self employed")
        $normalized = str_replace(['-', ' '], '_', $normalized);

        try {
            return $enumClass::from($normalized);
        } catch (\ValueError) {
            return null;
        }
    }
}
