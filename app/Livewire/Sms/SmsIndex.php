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
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorImpl;
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

    // View campaign modal — opened by clicking the eye icon on a grouped row.
    public bool $showMessageModal = false;

    public ?string $viewingCampaignKey = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [SmsLog::class, $branch]);
        $this->branch = $branch;
    }

    /**
     * Apply the page's filter state to a base SmsLog query. Used by every
     * paginator/exporter/stat method on this component so that filtering stays
     * consistent.
     */
    protected function applyFilters(Builder $query): Builder
    {
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

        if ($this->quickFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $this->applyDateRange($query, 'created_at');

        return $query;
    }

    /**
     * Paginated list of campaigns. Each campaign groups every SmsLog that
     * shares a `provider_message_id` (a TextTango campaign id). Rows without
     * a provider id — pending sends, or sends that errored before submission
     * — form their own single-row groups keyed by the row's own id, so they
     * still appear in the list.
     *
     * @return LengthAwarePaginator<int, array{
     *     key: string,
     *     created_at: Carbon|null,
     *     message: string,
     *     message_type: ?SmsType,
     *     status: SmsStatus,
     *     recipient_count: int,
     *     total_cost: float,
     *     currency: ?string,
     *     provider_message_id: ?string,
     *     logs: Collection<int, SmsLog>,
     * }>
     */
    #[Computed]
    public function smsRecords(): LengthAwarePaginator
    {
        $perPage = 25;
        $page = LengthAwarePaginatorImpl::resolveCurrentPage();

        // Build a subquery that returns one row per campaign key, ordered by
        // the most recent log in that campaign. We paginate the subquery and
        // then load every log for the visible group keys.
        $groupKey = 'COALESCE(provider_message_id, CAST(id AS CHAR))';

        $groupsQuery = $this->applyFilters(SmsLog::query()->where('branch_id', $this->branch->id))
            ->selectRaw("{$groupKey} as group_key, MAX(created_at) as latest_at")
            ->groupBy('group_key')
            ->orderByDesc('latest_at');

        $totalGroups = (clone $groupsQuery)->getQuery()->getCountForPagination();

        $pageGroups = (clone $groupsQuery)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $groupKeys = $pageGroups->pluck('group_key')->all();

        // Pull every log belonging to the visible groups in a single query.
        // We re-apply filters here too so that, e.g., `statusFilter=failed`
        // doesn't accidentally pull a Delivered log into a campaign group
        // when computing aggregates.
        $logs = $this->applyFilters(SmsLog::query()->where('branch_id', $this->branch->id))
            ->with(['member'])
            ->whereRaw("{$groupKey} IN (".implode(',', array_fill(0, max(count($groupKeys), 1), '?')).')', $groupKeys ?: [''])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (SmsLog $log): string => $log->provider_message_id ?: (string) $log->id);

        $campaigns = $pageGroups->map(function ($row) use ($logs): array {
            $key = (string) $row->group_key;
            $rows = $logs->get($key) ?? collect();

            return $this->buildCampaignSummary($key, $rows);
        });

        return new LengthAwarePaginatorImpl(
            $campaigns->values(),
            $totalGroups,
            $perPage,
            $page,
            ['path' => LengthAwarePaginatorImpl::resolveCurrentPath()],
        );
    }

    /**
     * Aggregate a campaign's per-recipient logs into the summary row shown in
     * the table. Status uses worst-case rollup: any Failed → Failed; otherwise
     * any Pending → Pending; otherwise if every recipient is Delivered →
     * Delivered; otherwise Sent.
     *
     * @param  Collection<int, SmsLog>  $logs
     * @return array{
     *     key: string,
     *     created_at: Carbon|null,
     *     message: string,
     *     message_type: ?SmsType,
     *     status: SmsStatus,
     *     recipient_count: int,
     *     total_cost: float,
     *     currency: ?string,
     *     provider_message_id: ?string,
     *     logs: Collection<int, SmsLog>,
     * }
     */
    protected function buildCampaignSummary(string $key, Collection $logs): array
    {
        $first = $logs->first();
        $statuses = $logs->map(fn (SmsLog $log): ?SmsStatus => $log->status);

        if ($statuses->contains(SmsStatus::Failed)) {
            $aggregated = SmsStatus::Failed;
        } elseif ($statuses->contains(SmsStatus::Pending)) {
            $aggregated = SmsStatus::Pending;
        } elseif ($statuses->every(fn (?SmsStatus $s): bool => $s === SmsStatus::Delivered) && $logs->isNotEmpty()) {
            $aggregated = SmsStatus::Delivered;
        } else {
            $aggregated = SmsStatus::Sent;
        }

        return [
            'key' => $key,
            'created_at' => $first?->created_at,
            'message' => (string) ($first?->message ?? ''),
            'message_type' => $first?->message_type,
            'status' => $aggregated,
            'recipient_count' => $logs->count(),
            'total_cost' => (float) $logs->sum(fn (SmsLog $log): float => (float) ($log->cost ?? 0)),
            'currency' => $first?->currency,
            'provider_message_id' => $first?->provider_message_id,
            'logs' => $logs->values(),
        ];
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
        // Stats stay at the per-recipient level so the totals reflect the
        // actual delivery work the branch has done — counting "delivered"
        // by campaign would understate volume on a 100-recipient blast.
        $baseQuery = $this->applyFilters(SmsLog::query()->where('branch_id', $this->branch->id));

        return [
            'total' => (clone $baseQuery)->count(),
            'delivered' => (clone $baseQuery)->where('status', SmsStatus::Delivered)->count(),
            'failed' => (clone $baseQuery)->where('status', SmsStatus::Failed)->count(),
            'pending' => (clone $baseQuery)->where('status', SmsStatus::Pending)->count(),
            'cost' => (clone $baseQuery)->sum('cost'),
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

    public function viewMessage(string $campaignKey): void
    {
        $this->viewingCampaignKey = $campaignKey;
        $this->showMessageModal = true;
    }

    public function closeMessageModal(): void
    {
        $this->showMessageModal = false;
        $this->viewingCampaignKey = null;
    }

    /**
     * Hydrate the recipients list when the modal is open. We re-query so the
     * modal always reflects the current state (delivery webhooks may have
     * arrived since the page was rendered).
     *
     * @return array{
     *     key: string,
     *     created_at: Carbon|null,
     *     message: string,
     *     message_type: ?SmsType,
     *     status: SmsStatus,
     *     recipient_count: int,
     *     total_cost: float,
     *     currency: ?string,
     *     provider_message_id: ?string,
     *     logs: Collection<int, SmsLog>,
     * }|null
     */
    #[Computed]
    public function viewingCampaign(): ?array
    {
        if (! $this->showMessageModal || $this->viewingCampaignKey === null) {
            return null;
        }

        $key = $this->viewingCampaignKey;
        $logs = SmsLog::query()
            ->with(['member'])
            ->where('branch_id', $this->branch->id)
            ->where(function ($q) use ($key): void {
                $q->where('provider_message_id', $key)->orWhere('id', $key);
            })
            ->orderBy('created_at')
            ->get();

        if ($logs->isEmpty()) {
            return null;
        }

        return $this->buildCampaignSummary($key, $logs);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [SmsLog::class, $this->branch]);

        // CSV export stays per-recipient so finance/audit consumers see one
        // row per delivery — grouping is a presentation concern.
        $records = $this->applyFilters(SmsLog::query()->where('branch_id', $this->branch->id))
            ->with(['member'])
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

    public function render(): Factory|View
    {
        return view('livewire.sms.sms-index');
    }
}
