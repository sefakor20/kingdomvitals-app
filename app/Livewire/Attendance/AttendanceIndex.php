<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\CheckInMethod;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class AttendanceIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

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
    public function attendanceRecords(): LengthAwarePaginator
    {
        $query = Attendance::where('branch_id', $this->branch->id);

        // Search includes member OR visitor relationships, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('visitor', function ($visitorQuery) use ($search): void {
                        $visitorQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'serviceFilter', 'service_id');

        // Apply type filter (custom logic for null checks)
        if ($this->typeFilter === 'member') {
            $query->whereNotNull('member_id');
        } elseif ($this->typeFilter === 'visitor') {
            $query->whereNotNull('visitor_id');
        }

        $this->applyEnumFilter($query, 'methodFilter', 'check_in_method');

        // Apply quick filter (custom logic for date range shortcuts)
        if ($this->quickFilter === 'today') {
            $query->whereDate('date', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $this->applyDateRange($query, 'date');

        return $query->with(['member', 'visitor', 'service'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedServiceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
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
        // Query database directly for stats (not from paginated collection)
        $baseQuery = Attendance::where('branch_id', $this->branch->id);

        // Apply same filters as main query
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $baseQuery->where(function ($q) use ($search): void {
                $q->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('visitor', function ($visitorQuery) use ($search): void {
                        $visitorQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($baseQuery, 'serviceFilter', 'service_id');

        if ($this->typeFilter === 'member') {
            $baseQuery->whereNotNull('member_id');
        } elseif ($this->typeFilter === 'visitor') {
            $baseQuery->whereNotNull('visitor_id');
        }

        $this->applyEnumFilter($baseQuery, 'methodFilter', 'check_in_method');

        if ($this->quickFilter === 'today') {
            $baseQuery->whereDate('date', today());
        } elseif ($this->quickFilter === 'this_week') {
            $baseQuery->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $baseQuery->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $this->applyDateRange($baseQuery, 'date');

        return [
            'total' => (clone $baseQuery)->count(),
            'members' => (clone $baseQuery)->whereNotNull('member_id')->count(),
            'visitors' => (clone $baseQuery)->whereNotNull('visitor_id')->count(),
            'today' => (clone $baseQuery)->whereDate('date', today())->count(),
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
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->serviceFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->dateFrom)) {
            return true;
        }
        if ($this->isFilterActive($this->dateTo)) {
            return true;
        }
        if ($this->isFilterActive($this->typeFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->methodFilter)) {
            return true;
        }
        return $this->isFilterActive($this->quickFilter);
    }

    public function applyQuickFilter(string $filter): void
    {
        // Clear date range when using quick filter
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->quickFilter = $filter;

        $this->resetPage();
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

        $this->resetPage();
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

    public function checkOut(Attendance $attendance): void
    {
        $this->authorize('update', $attendance);

        if ($attendance->check_out_time !== null) {
            return;
        }

        $attendance->update(['check_out_time' => now()->format('H:i')]);

        unset($this->attendanceRecords);
        unset($this->attendanceStats);

        $this->dispatch('attendance-checked-out');
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Attendance::class, $this->branch]);

        // Build query with same filters but get all records (not paginated)
        $query = Attendance::where('branch_id', $this->branch->id);

        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('visitor', function ($visitorQuery) use ($search): void {
                        $visitorQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'serviceFilter', 'service_id');

        if ($this->typeFilter === 'member') {
            $query->whereNotNull('member_id');
        } elseif ($this->typeFilter === 'visitor') {
            $query->whereNotNull('visitor_id');
        }

        $this->applyEnumFilter($query, 'methodFilter', 'check_in_method');

        if ($this->quickFilter === 'today') {
            $query->whereDate('date', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $this->applyDateRange($query, 'date');

        $records = $query->with(['member', 'visitor', 'service'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();

        $filename = sprintf(
            'attendance_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($records): void {
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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.attendance.attendance-index');
    }
}
