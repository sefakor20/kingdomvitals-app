<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class AttendanceIndex extends Component
{
    public Branch $branch;

    // Search and filters
    public string $search = '';

    public ?string $serviceFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $typeFilter = ''; // 'member', 'visitor', or '' (all)

    public string $methodFilter = ''; // Check-in method filter

    // Quick filter (for today, this_week, this_month)
    public string $quickFilter = '';

    // Delete confirmation
    public bool $showDeleteModal = false;

    public ?Attendance $deletingAttendance = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Attendance::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function attendanceRecords(): Collection
    {
        $query = Attendance::where('branch_id', $this->branch->id);

        // Apply search filter
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('member', function ($memberQuery) use ($search) {
                    $memberQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('visitor', function ($visitorQuery) use ($search) {
                        $visitorQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply service filter
        if ($this->serviceFilter) {
            $query->where('service_id', $this->serviceFilter);
        }

        // Apply type filter
        if ($this->typeFilter === 'member') {
            $query->whereNotNull('member_id');
        } elseif ($this->typeFilter === 'visitor') {
            $query->whereNotNull('visitor_id');
        }

        // Apply method filter
        if ($this->methodFilter) {
            $query->where('check_in_method', $this->methodFilter);
        }

        // Apply quick filter
        if ($this->quickFilter === 'today') {
            $query->whereDate('date', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        // Apply date range filters
        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        return $query->with(['member', 'visitor', 'service'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function checkInMethods(): array
    {
        return CheckInMethod::cases();
    }

    #[Computed]
    public function attendanceStats(): array
    {
        $records = $this->attendanceRecords;

        return [
            'total' => $records->count(),
            'members' => $records->whereNotNull('member_id')->count(),
            'visitors' => $records->whereNotNull('visitor_id')->count(),
            'today' => $records->filter(fn ($r) => $r->date && $r->date->isToday())->count(),
        ];
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Attendance::class, $this->branch]);
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->serviceFilter !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->typeFilter !== ''
            || $this->methodFilter !== ''
            || $this->quickFilter !== '';
    }

    public function applyQuickFilter(string $filter): void
    {
        // Clear date range when using quick filter
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->quickFilter = $filter;

        unset($this->attendanceRecords);
        unset($this->attendanceStats);
        unset($this->hasActiveFilters);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'serviceFilter', 'dateFrom', 'dateTo',
            'typeFilter', 'methodFilter', 'quickFilter',
        ]);

        unset($this->attendanceRecords);
        unset($this->attendanceStats);
        unset($this->hasActiveFilters);
    }

    public function confirmDelete(Attendance $attendance): void
    {
        $this->authorize('delete', $attendance);
        $this->deletingAttendance = $attendance;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingAttendance);

        $this->deletingAttendance->delete();

        unset($this->attendanceRecords);
        unset($this->attendanceStats);

        $this->showDeleteModal = false;
        $this->deletingAttendance = null;
        $this->dispatch('attendance-deleted');
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingAttendance = null;
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Attendance::class, $this->branch]);

        $records = $this->attendanceRecords;

        $filename = sprintf(
            'attendance_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Service',
                'Attendee',
                'Type',
                'Check-in Time',
                'Check-out Time',
                'Check-in Method',
                'Notes',
            ]);

            // Data rows
            foreach ($records as $record) {
                $attendeeName = $record->member?->fullName() ?? $record->visitor?->fullName() ?? '-';
                $type = $record->member_id ? 'Member' : 'Visitor';

                fputcsv($handle, [
                    $record->date?->format('Y-m-d') ?? '',
                    $record->service?->name ?? '',
                    $attendeeName,
                    $type,
                    $record->check_in_time ? substr($record->check_in_time, 0, 5) : '',
                    $record->check_out_time ? substr($record->check_out_time, 0, 5) : '',
                    ucfirst($record->check_in_method->value),
                    $record->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.attendance.attendance-index');
    }
}
