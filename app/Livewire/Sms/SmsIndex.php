<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Services\PlanAccessService;
use App\Services\TextTangoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class SmsIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // Quick filter (for today, this_week, this_month)
    public string $quickFilter = '';

    // View message modal
    public bool $showMessageModal = false;

    public ?SmsLog $viewingMessage = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [SmsLog::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function smsRecords(): LengthAwarePaginator
    {
        $query = SmsLog::where('branch_id', $this->branch->id);

        // Search includes relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'typeFilter', 'message_type');

        // Apply quick filter (custom logic for date range shortcuts)
        if ($this->quickFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $this->applyDateRange($query, 'created_at');

        return $query->with(['member'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
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

    #[Computed]
    public function smsStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = SmsLog::where('branch_id', $this->branch->id);

        // Apply search filter
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $baseQuery->where(function ($q) use ($search): void {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($baseQuery, 'statusFilter', 'status');
        $this->applyEnumFilter($baseQuery, 'typeFilter', 'message_type');

        // Apply quick filter
        if ($this->quickFilter === 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($this->quickFilter === 'this_week') {
            $baseQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $baseQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $this->applyDateRange($baseQuery, 'created_at');

        $total = (clone $baseQuery)->count();
        $totalCost = (clone $baseQuery)->sum('cost');
        $deliveredCount = (clone $baseQuery)->where('status', SmsStatus::Delivered)->count();
        $failedCount = (clone $baseQuery)->where('status', SmsStatus::Failed)->count();
        $pendingCount = (clone $baseQuery)->where('status', SmsStatus::Pending)->count();

        return [
            'total' => $total,
            'delivered' => $deliveredCount,
            'failed' => $failedCount,
            'pending' => $pendingCount,
            'cost' => $totalCost,
            'currency' => tenant()->getCurrencyCode(),
        ];
    }

    #[Computed]
    public function smsStatuses(): array
    {
        return SmsStatus::cases();
    }

    #[Computed]
    public function smsTypes(): array
    {
        return SmsType::cases();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->statusFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->typeFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->dateFrom)) {
            return true;
        }
        if ($this->isFilterActive($this->dateTo)) {
            return true;
        }
        return $this->isFilterActive($this->quickFilter);
    }

    #[Computed]
    public function accountBalance(): array
    {
        $service = TextTangoService::forBranch($this->branch);

        if (! $service->isConfigured()) {
            return ['success' => false, 'error' => 'SMS service not configured. Please configure SMS settings in branch settings.'];
        }

        return $service->getBalance();
    }

    #[Computed]
    public function isSmsConfigured(): bool
    {
        return TextTangoService::forBranch($this->branch)->isConfigured();
    }

    /**
     * Get SMS quota information for display.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function smsQuota(): array
    {
        return app(PlanAccessService::class)->getSmsQuota();
    }

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return app(PlanAccessService::class)->isQuotaWarning('sms', 80);
    }

    public function applyQuickFilter(string $filter): void
    {
        // Clear date range when using quick filter
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->quickFilter = $filter;

        $this->resetPage();
        unset($this->smsRecords);
        unset($this->smsStats);
        unset($this->hasActiveFilters);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'typeFilter',
            'dateFrom', 'dateTo', 'quickFilter',
        ]);

        $this->resetPage();
        unset($this->smsRecords);
        unset($this->smsStats);
        unset($this->hasActiveFilters);
    }

    public function viewMessage(SmsLog $smsLog): void
    {
        $this->viewingMessage = $smsLog;
        $this->showMessageModal = true;
    }

    public function closeMessageModal(): void
    {
        $this->showMessageModal = false;
        $this->viewingMessage = null;
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [SmsLog::class, $this->branch]);

        // Build query for export (all filtered records, not paginated)
        $query = SmsLog::where('branch_id', $this->branch->id);

        // Apply search filter
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'typeFilter', 'message_type');

        // Apply quick filter
        if ($this->quickFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $this->applyDateRange($query, 'created_at');

        $records = $query->with(['member'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = sprintf(
            'sms_logs_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Recipient',
                'Phone Number',
                'Message',
                'Type',
                'Status',
                'Cost',
                'Provider ID',
                'Error',
            ]);

            // Data rows
            foreach ($records as $record) {
                $recipientName = $record->member?->fullName() ?? '-';

                fputcsv($handle, [
                    $record->created_at?->format('Y-m-d H:i') ?? '',
                    $recipientName,
                    $record->phone_number ?? '',
                    $record->message ?? '',
                    $record->message_type?->value ?? '',
                    $record->status?->value ?? '',
                    number_format((float) $record->cost, 4),
                    $record->provider_message_id ?? '',
                    $record->error_message ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.sms.sms-index');
    }
}
