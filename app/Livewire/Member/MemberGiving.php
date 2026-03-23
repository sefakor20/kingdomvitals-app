<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Enums\Currency;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\GivingStatementService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.member')]
class MemberGiving extends Component
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
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function donations(): LengthAwarePaginator
    {
        return Donation::query()
            ->where('member_id', $this->member->id)
            ->whereYear('donation_date', $this->year)
            ->orderByDesc('donation_date')
            ->paginate(20);
    }

    #[Computed]
    public function yearlyTotal(): float
    {
        return (float) Donation::query()
            ->where('member_id', $this->member->id)
            ->whereYear('donation_date', $this->year)
            ->sum('amount');
    }

    #[Computed]
    public function monthlyTotals(): array
    {
        $totals = Donation::query()
            ->selectRaw('MONTH(donation_date) as month, SUM(amount) as total')
            ->where('member_id', $this->member->id)
            ->whereYear('donation_date', $this->year)
            ->groupByRaw('MONTH(donation_date)')
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
        $years = Donation::query()
            ->selectRaw('YEAR(donation_date) as year')
            ->where('member_id', $this->member->id)
            ->groupByRaw('YEAR(donation_date)')
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

    public function downloadStatement(): StreamedResponse
    {
        return app(GivingStatementService::class)
            ->downloadStatement($this->member, $this->year);
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-giving');
    }
}
