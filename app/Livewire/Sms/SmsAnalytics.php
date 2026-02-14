<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\Currency;
use App\Enums\SmsStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsLog;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SmsAnalytics extends Component
{
    public Branch $branch;

    public int $period = 30;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [SmsLog::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    public function setPeriod(int $days): void
    {
        $this->period = $days;

        // Clear computed property caches
        unset($this->deliveryRateData);
        unset($this->messagesByTypeData);
        unset($this->statusDistributionData);
        unset($this->dailyCostData);
        unset($this->summaryStats);

        // Dispatch event to refresh charts
        $this->dispatch('charts-updated');
    }

    #[Computed]
    public function deliveryRateData(): array
    {
        $results = SmsLog::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered', [SmsStatus::Delivered->value])
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $rate = $row->total > 0 ? round(($row->delivered / $row->total) * 100, 1) : 0;
            $data[] = $rate;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function messagesByTypeData(): array
    {
        $results = SmsLog::query()
            ->selectRaw('message_type, COUNT(*) as count')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupBy('message_type')
            ->get();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            if ($row->count > 0) {
                $messageType = $row->message_type instanceof \App\Enums\SmsType
                    ? $row->message_type->value
                    : ($row->message_type ?? 'custom');
                $labels[] = ucfirst(str_replace('_', ' ', $messageType));
                $data[] = $row->count;
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function statusDistributionData(): array
    {
        $statusColors = [
            'pending' => '#f59e0b',   // yellow
            'sent' => '#3b82f6',      // blue
            'delivered' => '#22c55e', // green
            'failed' => '#ef4444',    // red
        ];

        $results = SmsLog::query()
            ->selectRaw('status, COUNT(*) as count')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->count > 0) {
                $status = $row->status instanceof SmsStatus
                    ? $row->status->value
                    : $row->status;
                $labels[] = ucfirst($status);
                $data[] = $row->count;
                $colors[] = $statusColors[$status] ?? '#71717a';
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    #[Computed]
    public function dailyCostData(): array
    {
        $results = SmsLog::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $data[] = (float) $row->total_cost;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function summaryStats(): array
    {
        $stats = SmsLog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered', [SmsStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [SmsStatus::Failed->value])
            ->selectRaw('COALESCE(SUM(cost), 0) as total_cost')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->first();

        $total = (int) $stats->total;
        $delivered = (int) $stats->delivered;
        $failed = (int) $stats->failed;
        $totalCost = (float) $stats->total_cost;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0,
            'total_cost' => $totalCost,
            'avg_cost_per_sms' => $total > 0 ? round($totalCost / $total, 4) : 0,
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.sms.sms-analytics');
    }
}
