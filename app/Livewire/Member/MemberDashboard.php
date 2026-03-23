<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Enums\Currency;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.member')]
class MemberDashboard extends Component
{
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
    public function totalGivingThisYear(): float
    {
        return (float) Donation::query()
            ->where('member_id', $this->member->id)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');
    }

    #[Computed]
    public function totalGivingThisMonth(): float
    {
        return (float) Donation::query()
            ->where('member_id', $this->member->id)
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');
    }

    #[Computed]
    public function attendanceThisMonth(): int
    {
        return Attendance::query()
            ->where('member_id', $this->member->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
    }

    #[Computed]
    public function attendanceThisYear(): int
    {
        return Attendance::query()
            ->where('member_id', $this->member->id)
            ->whereYear('date', now()->year)
            ->count();
    }

    #[Computed]
    public function recentAttendance(): Collection
    {
        return Attendance::query()
            ->with('service')
            ->where('member_id', $this->member->id)
            ->orderByDesc('date')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentDonations(): Collection
    {
        return Donation::query()
            ->where('member_id', $this->member->id)
            ->orderByDesc('donation_date')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function activePledges(): Collection
    {
        return Pledge::query()
            ->with('campaign')
            ->where('member_id', $this->member->id)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function pledgeProgress(): array
    {
        $pledges = $this->activePledges;

        if ($pledges->isEmpty()) {
            return [];
        }

        return $pledges->map(function (Pledge $pledge) {
            $paid = (float) Donation::query()
                ->where('member_id', $this->member->id)
                ->where('pledge_id', $pledge->id)
                ->sum('amount');

            return [
                'pledge' => $pledge,
                'paid' => $paid,
                'remaining' => max(0, $pledge->amount - $paid),
                'percentage' => $pledge->amount > 0
                    ? min(100, round(($paid / $pledge->amount) * 100, 1))
                    : 0,
            ];
        })->toArray();
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-dashboard');
    }
}
