<?php

declare(strict_types=1);

namespace App\Livewire\Email;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EmailAnalytics extends Component
{
    public Branch $branch;

    public int $period = 30;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [EmailLog::class, $branch]);
        $this->branch = $branch;
    }

    public function setPeriod(int $days): void
    {
        $this->period = $days;

        // Clear computed property caches
        unset($this->deliveryRateData);
        unset($this->messagesByTypeData);
        unset($this->statusDistributionData);
        unset($this->openRateData);
        unset($this->summaryStats);

        // Dispatch event to refresh charts
        $this->dispatch('charts-updated');
    }

    #[Computed]
    public function deliveryRateData(): array
    {
        $results = EmailLog::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered', [EmailStatus::Delivered->value])
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
    public function openRateData(): array
    {
        $results = EmailLog::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
            ->selectRaw('SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $labels = [];
        $openData = [];
        $clickData = [];

        foreach ($results as $row) {
            $labels[] = Carbon::parse($row->date)->format('M d');
            $openRate = $row->total > 0 ? round(($row->opened / $row->total) * 100, 1) : 0;
            $clickRate = $row->total > 0 ? round(($row->clicked / $row->total) * 100, 1) : 0;
            $openData[] = $openRate;
            $clickData[] = $clickRate;
        }

        return [
            'labels' => $labels,
            'openData' => $openData,
            'clickData' => $clickData,
        ];
    }

    #[Computed]
    public function messagesByTypeData(): array
    {
        $results = EmailLog::query()
            ->selectRaw('message_type, COUNT(*) as count')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->groupBy('message_type')
            ->get();

        $labels = [];
        $data = [];

        foreach ($results as $row) {
            if ($row->count > 0) {
                $messageType = $row->message_type instanceof EmailType
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
            'bounced' => '#f97316',   // orange
            'failed' => '#ef4444',    // red
        ];

        $results = EmailLog::query()
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
                $status = $row->status instanceof EmailStatus
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
    public function summaryStats(): array
    {
        $stats = EmailLog::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered', [EmailStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as bounced', [EmailStatus::Bounced->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [EmailStatus::Failed->value])
            ->selectRaw('SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened')
            ->selectRaw('SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked')
            ->where('branch_id', $this->branch->id)
            ->where('created_at', '>=', now()->subDays($this->period)->startOfDay())
            ->first();

        $total = (int) $stats->total;
        $delivered = (int) $stats->delivered;
        $bounced = (int) $stats->bounced;
        $failed = (int) $stats->failed;
        $opened = (int) $stats->opened;
        $clicked = (int) $stats->clicked;

        // If an email was opened, it was definitively delivered
        // (tracking pixel only loads if email reached inbox)
        $effectiveDelivered = max($delivered, $opened);

        // For rate calculations, use emails that weren't bounced/failed
        $sentSuccessfully = $total - $bounced - $failed;

        return [
            'total' => $total,
            'delivered' => $effectiveDelivered,
            'bounced' => $bounced,
            'failed' => $failed,
            'opened' => $opened,
            'clicked' => $clicked,
            'delivery_rate' => $total > 0 ? round(($effectiveDelivered / $total) * 100, 1) : 0,
            'open_rate' => $sentSuccessfully > 0 ? round(($opened / $sentSuccessfully) * 100, 1) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0,
        ];
    }

    public function render(): Factory|View
    {
        return view('livewire.email.email-analytics');
    }
}
