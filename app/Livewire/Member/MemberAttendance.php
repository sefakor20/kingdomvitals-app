<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Member;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.member')]
class MemberAttendance extends Component
{
    use WithPagination;

    #[Url]
    public int $year;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    #[Computed]
    public function member(): Member
    {
        return Member::where('user_id', auth()->id())
            ->whereNotNull('portal_activated_at')
            ->firstOrFail();
    }

    #[Computed]
    public function attendance(): LengthAwarePaginator
    {
        return Attendance::query()
            ->with('service')
            ->where('member_id', $this->member->id)
            ->whereYear('date', $this->year)
            ->orderByDesc('date')
            ->paginate(20);
    }

    #[Computed]
    public function yearlyTotal(): int
    {
        return Attendance::query()
            ->where('member_id', $this->member->id)
            ->whereYear('date', $this->year)
            ->count();
    }

    #[Computed]
    public function monthlyTotals(): array
    {
        $totals = Attendance::query()
            ->selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->where('member_id', $this->member->id)
            ->whereYear('date', $this->year)
            ->groupByRaw('MONTH(date)')
            ->pluck('total', 'month')
            ->toArray();

        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[$i] = $totals[$i] ?? 0;
        }

        return $result;
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Attendance::query()
            ->selectRaw('YEAR(date) as year')
            ->where('member_id', $this->member->id)
            ->groupByRaw('YEAR(date)')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            return [now()->year];
        }

        return $years;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
        $this->resetPage();
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-attendance');
    }
}
