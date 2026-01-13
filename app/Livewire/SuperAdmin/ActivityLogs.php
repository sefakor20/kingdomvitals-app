<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Livewire\Concerns\HasReportExport;
use App\Models\SuperAdminActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogs extends Component
{
    use HasReportExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $logs = $this->getFilteredLogs()->get();

        $data = $logs->map(fn (SuperAdminActivityLog $log): array => [
            'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
            'admin_name' => $log->superAdmin?->name ?? 'System',
            'action' => str_replace('_', ' ', $log->action),
            'description' => $log->description ?? '',
            'tenant_name' => $log->tenant?->name ?? '',
            'ip_address' => $log->ip_address ?? '',
        ]);

        $headers = [
            'Timestamp',
            'Admin Name',
            'Action',
            'Description',
            'Tenant Name',
            'IP Address',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_activity_logs',
            description: 'Exported activity logs to CSV',
            metadata: [
                'record_count' => $logs->count(),
                'filters' => [
                    'search' => $this->search,
                    'action' => $this->action,
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ],
            ],
        );

        $filename = 'activity-logs-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SuperAdminActivityLog>
     */
    private function getFilteredLogs(): \Illuminate\Database\Eloquent\Builder
    {
        return SuperAdminActivityLog::query()
            ->with(['superAdmin', 'tenant'])
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhereHas('superAdmin', function ($q): void {
                            $q->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->action, function ($query): void {
                $query->where('action', $this->action);
            })
            ->when($this->startDate, function ($query): void {
                $query->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query): void {
                $query->whereDate('created_at', '<=', $this->endDate);
            })
            ->latest('created_at');
    }

    public function render(): View
    {
        $actions = SuperAdminActivityLog::distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('livewire.super-admin.activity-logs', [
            'logs' => $this->getFilteredLogs()->paginate(25),
            'actions' => $actions,
        ])->layout('components.layouts.superadmin.app');
    }
}
