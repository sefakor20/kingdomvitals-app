<?php

namespace App\Livewire\DutyRosters;

use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.print')]
class DutyRosterPrint extends Component
{
    public Branch $branch;

    public ?string $month = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [DutyRoster::class, $branch]);
        $this->branch = $branch;

        // Get date range from query params
        $this->month = request()->query('month', now()->format('Y-m'));
        $this->startDate = request()->query('start');
        $this->endDate = request()->query('end');
    }

    #[Computed]
    public function dutyRosters(): Collection
    {
        $query = DutyRoster::where('branch_id', $this->branch->id)
            ->with(['service', 'preacher', 'liturgist', 'scriptures.reader', 'clusters'])
            ->orderBy('service_date');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('service_date', [
                Carbon::parse($this->startDate),
                Carbon::parse($this->endDate),
            ]);
        } elseif ($this->month) {
            $date = Carbon::parse($this->month.'-01');
            $query->whereYear('service_date', $date->year)
                ->whereMonth('service_date', $date->month);
        }

        return $query->get();
    }

    #[Computed]
    public function dateRangeDisplay(): string
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            return $start->format('F j, Y').' to '.$end->format('F j, Y');
        }

        if ($this->month) {
            return Carbon::parse($this->month.'-01')->format('F Y');
        }

        return now()->format('F Y');
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.duty-roster-print');
    }
}
