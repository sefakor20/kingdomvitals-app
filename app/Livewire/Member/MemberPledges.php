<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Enums\Currency;
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
class MemberPledges extends Component
{
    #[Computed]
    public function member(): Member
    {
        return app('currentMember');
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
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
    public function completedPledges(): Collection
    {
        return Pledge::query()
            ->with('campaign')
            ->where('member_id', $this->member->id)
            ->where('end_date', '<', now())
            ->orderByDesc('end_date')
            ->get();
    }

    #[Computed]
    public function pledgeProgress(): array
    {
        return $this->activePledges->map(function (Pledge $pledge) {
            $paid = Donation::query()
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
        return view('livewire.member.member-pledges');
    }
}
