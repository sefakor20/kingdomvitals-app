<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Livewire\Attributes\Computed;

trait HasReportFilters
{
    public int $period = 30;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function setPeriod(int $days): void
    {
        $this->period = $days;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->clearReportCaches();
        $this->dispatch('charts-updated');
    }

    public function applyCustomDateRange(): void
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->period = 0;
            $this->clearReportCaches();
            $this->dispatch('charts-updated');
        }
    }

    #[Computed]
    public function startDate(): Carbon
    {
        if ($this->dateFrom) {
            return Carbon::parse($this->dateFrom)->startOfDay();
        }

        return now()->subDays($this->period)->startOfDay();
    }

    #[Computed]
    public function endDate(): Carbon
    {
        if ($this->dateTo) {
            return Carbon::parse($this->dateTo)->endOfDay();
        }

        return now()->endOfDay();
    }

    #[Computed]
    public function periodLabel(): string
    {
        if ($this->dateFrom && $this->dateTo) {
            return Carbon::parse($this->dateFrom)->format('M d, Y').' - '.Carbon::parse($this->dateTo)->format('M d, Y');
        }

        return match ($this->period) {
            7 => __('Last 7 days'),
            30 => __('Last 30 days'),
            90 => __('Last 90 days'),
            365 => __('Last 12 months'),
            default => __('Last :days days', ['days' => $this->period]),
        };
    }

    /**
     * Override this method in the component to clear specific computed caches.
     */
    protected function clearReportCaches(): void
    {
        // Override in component to unset specific computed properties
    }
}
