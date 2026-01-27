<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\VisitorStatus;
use App\Models\Tenant\Visitor;
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

class VisitorImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
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

            Visitor::create($this->mapRowToVisitor($row));
            $this->importedCount++;
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'visit_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:new,followed_up,returning,converted,not_interested,'],
            'how_did_you_hear' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'visit_date.required' => 'Visit date is required.',
            'visit_date.date' => 'Visit date must be a valid date.',
            'email.email' => 'Email must be a valid email address.',
            'status.in' => 'Status must be new, followed_up, returning, converted, or not_interested.',
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

        $query = Visitor::where('branch_id', $this->branchId);

        if ($email) {
            $query->where('email', $email);
        }

        if ($phone) {
            if ($email) {
                $query->orWhere(function ($q) use ($phone) {
                    $q->where('branch_id', $this->branchId)
                        ->where('phone', $phone);
                });
            } else {
                $query->where('phone', $phone);
            }
        }

        return $query->exists();
    }

    protected function mapRowToVisitor(Collection $row): array
    {
        return [
            'branch_id' => $this->branchId,
            'first_name' => $this->normalizeValue($row['first_name']),
            'last_name' => $this->normalizeValue($row['last_name']),
            'email' => $this->normalizeValue($row['email'] ?? null),
            'phone' => $this->normalizeValue($row['phone'] ?? null),
            'visit_date' => $this->parseDate($row['visit_date']),
            'status' => $this->parseEnum($row['status'] ?? null, VisitorStatus::class) ?? VisitorStatus::New,
            'how_did_you_hear' => $this->normalizeValue($row['how_did_you_hear'] ?? null),
            'notes' => $this->normalizeValue($row['notes'] ?? null),
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

        // Handle variations (e.g., "not interested" -> "not_interested")
        $normalized = str_replace(['-', ' '], '_', $normalized);

        try {
            return $enumClass::from($normalized);
        } catch (\ValueError) {
            return null;
        }
    }
}
