<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use App\Services\TextTangoService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class SmsIndex extends Component
{
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
    public function smsRecords(): Collection
    {
        $query = SmsLog::where('branch_id', $this->branch->id);

        // Apply search filter (phone number or member name)
        if ($this->search !== '' && $this->search !== '0') {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply status filter
        if ($this->statusFilter !== '' && $this->statusFilter !== '0') {
            $query->where('status', $this->statusFilter);
        }

        // Apply type filter
        if ($this->typeFilter !== '' && $this->typeFilter !== '0') {
            $query->where('message_type', $this->typeFilter);
        }

        // Apply quick filter
        if ($this->quickFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->quickFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->quickFilter === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // Apply date range filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->with(['member'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function smsStats(): array
    {
        $records = $this->smsRecords;

        $totalCost = $records->sum('cost');
        $deliveredCount = $records->where('status', SmsStatus::Delivered)->count();
        $failedCount = $records->where('status', SmsStatus::Failed)->count();
        $pendingCount = $records->where('status', SmsStatus::Pending)->count();

        return [
            'total' => $records->count(),
            'delivered' => $deliveredCount,
            'failed' => $failedCount,
            'pending' => $pendingCount,
            'cost' => $totalCost,
            'currency' => 'GHS',
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
        return $this->search !== ''
            || $this->statusFilter !== ''
            || $this->typeFilter !== ''
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->quickFilter !== '';
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

    public function applyQuickFilter(string $filter): void
    {
        // Clear date range when using quick filter
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->quickFilter = $filter;

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

        $records = $this->smsRecords;

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
