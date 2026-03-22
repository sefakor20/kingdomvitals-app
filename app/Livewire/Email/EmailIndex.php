<?php

declare(strict_types=1);

namespace App\Livewire\Email;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use App\Services\PlanAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class EmailIndex extends Component
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

    public ?EmailLog $viewingMessage = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [EmailLog::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function emailRecords(): LengthAwarePaginator
    {
        $query = EmailLog::where('branch_id', $this->branch->id);

        // Search includes relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('email_address', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
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
    public function emailStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = EmailLog::where('branch_id', $this->branch->id);

        // Apply search filter
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $baseQuery->where(function ($q) use ($search): void {
                $q->where('email_address', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
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
        $deliveredCount = (clone $baseQuery)->where('status', EmailStatus::Delivered)->count();
        $openedCount = (clone $baseQuery)->whereNotNull('opened_at')->count();
        $clickedCount = (clone $baseQuery)->whereNotNull('clicked_at')->count();
        $bouncedCount = (clone $baseQuery)->where('status', EmailStatus::Bounced)->count();
        $failedCount = (clone $baseQuery)->where('status', EmailStatus::Failed)->count();
        $pendingCount = (clone $baseQuery)->where('status', EmailStatus::Pending)->count();

        return [
            'total' => $total,
            'delivered' => $deliveredCount,
            'opened' => $openedCount,
            'clicked' => $clickedCount,
            'bounced' => $bouncedCount,
            'failed' => $failedCount,
            'pending' => $pendingCount,
            'open_rate' => $deliveredCount > 0 ? round(($openedCount / $deliveredCount) * 100, 1) : 0,
            'click_rate' => $openedCount > 0 ? round(($clickedCount / $openedCount) * 100, 1) : 0,
        ];
    }

    #[Computed]
    public function emailStatuses(): array
    {
        return EmailStatus::cases();
    }

    #[Computed]
    public function emailTypes(): array
    {
        return EmailType::cases();
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

    /**
     * Get email quota information for display.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function emailQuota(): array
    {
        return app(PlanAccessService::class)->getEmailQuota();
    }

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return app(PlanAccessService::class)->isQuotaWarning('email', 80);
    }

    public function applyQuickFilter(string $filter): void
    {
        // Clear date range when using quick filter
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->quickFilter = $filter;

        $this->resetPage();
        unset($this->emailRecords);
        unset($this->emailStats);
        unset($this->hasActiveFilters);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'typeFilter',
            'dateFrom', 'dateTo', 'quickFilter',
        ]);

        $this->resetPage();
        unset($this->emailRecords);
        unset($this->emailStats);
        unset($this->hasActiveFilters);
    }

    public function viewMessage(EmailLog $emailLog): void
    {
        $this->viewingMessage = $emailLog;
        $this->showMessageModal = true;
    }

    public function closeMessageModal(): void
    {
        $this->showMessageModal = false;
        $this->viewingMessage = null;
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [EmailLog::class, $this->branch]);

        // Build query for export (all filtered records, not paginated)
        $query = EmailLog::where('branch_id', $this->branch->id);

        // Apply search filter
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('email_address', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
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
            'email_logs_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($records): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Recipient',
                'Email Address',
                'Subject',
                'Type',
                'Status',
                'Sent At',
                'Opened At',
                'Clicked At',
                'Error',
            ]);

            // Data rows
            foreach ($records as $record) {
                $recipientName = $record->member?->fullName() ?? '-';

                fputcsv($handle, [
                    $record->created_at?->format('Y-m-d H:i') ?? '',
                    $recipientName,
                    $record->email_address ?? '',
                    $record->subject ?? '',
                    $record->message_type?->value ?? '',
                    $record->status?->value ?? '',
                    $record->sent_at?->format('Y-m-d H:i') ?? '',
                    $record->opened_at?->format('Y-m-d H:i') ?? '',
                    $record->clicked_at?->format('Y-m-d H:i') ?? '',
                    $record->error_message ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render(): Factory|View
    {
        return view('livewire.email.email-index');
    }
}
