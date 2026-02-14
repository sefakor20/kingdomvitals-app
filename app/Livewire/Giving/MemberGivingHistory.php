<?php

declare(strict_types=1);

namespace App\Livewire\Giving;

use App\Enums\Currency;
use App\Enums\DonationType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Services\DonationReceiptService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class MemberGivingHistory extends Component
{
    public Branch $branch;

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    #[Url]
    public string $year = '';

    public function mount(Branch $branch): void
    {
        $this->branch = $branch;
        $this->year = (string) now()->year;
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function member(): ?Member
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return Member::where('primary_branch_id', $this->branch->id)
            ->where('email', $user->email)
            ->first();
    }

    #[Computed]
    public function donations(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $query = Donation::where('branch_id', $this->branch->id)
            ->where(function ($q) use ($user): void {
                // Match donations by member email or donor_email
                $q->whereHas('member', function ($memberQuery) use ($user): void {
                    $memberQuery->where('email', $user->email);
                })
                    ->orWhere('donor_email', $user->email);
            });

        if ($this->typeFilter !== '' && $this->typeFilter !== '0') {
            $query->where('donation_type', $this->typeFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('donation_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('donation_date', '<=', $this->dateTo);
        }

        return $query->orderBy('donation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function givingStats(): array
    {
        $user = auth()->user();
        if (! $user) {
            return ['total' => 0, 'count' => 0, 'thisYear' => 0, 'yearCount' => 0];
        }

        $baseQuery = Donation::where('branch_id', $this->branch->id)
            ->where(function ($q) use ($user): void {
                $q->whereHas('member', function ($memberQuery) use ($user): void {
                    $memberQuery->where('email', $user->email);
                })
                    ->orWhere('donor_email', $user->email);
            });

        $total = (clone $baseQuery)->sum('amount');
        $count = (clone $baseQuery)->count();

        $yearStart = Carbon::create((int) $this->year, 1, 1)->startOfYear();
        $yearEnd = Carbon::create((int) $this->year, 12, 31)->endOfYear();

        $thisYear = (clone $baseQuery)
            ->whereBetween('donation_date', [$yearStart, $yearEnd])
            ->sum('amount');

        $yearCount = (clone $baseQuery)
            ->whereBetween('donation_date', [$yearStart, $yearEnd])
            ->count();

        return [
            'total' => $total,
            'count' => $count,
            'thisYear' => $thisYear,
            'yearCount' => $yearCount,
        ];
    }

    #[Computed]
    public function donationTypes(): array
    {
        return DonationType::cases();
    }

    #[Computed]
    public function availableYears(): array
    {
        $currentYear = now()->year;
        $years = [];

        for ($i = 0; $i < 5; $i++) {
            $years[] = $currentYear - $i;
        }

        return $years;
    }

    #[Computed]
    public function recurringDonations(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        return Donation::where('branch_id', $this->branch->id)
            ->where('is_recurring', true)
            ->whereNotNull('paystack_subscription_code')
            ->where(function ($q) use ($user): void {
                $q->whereHas('member', function ($memberQuery) use ($user): void {
                    $memberQuery->where('email', $user->email);
                })
                    ->orWhere('donor_email', $user->email);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->typeFilter !== ''
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }

    public function clearFilters(): void
    {
        $this->reset(['typeFilter', 'dateFrom', 'dateTo']);
        unset($this->donations);
        unset($this->hasActiveFilters);
    }

    public function setYear(string $year): void
    {
        $this->year = $year;
        unset($this->givingStats);
    }

    public function downloadReceipt(Donation $donation): StreamedResponse
    {
        // Verify this donation belongs to the current user
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $belongsToUser = $donation->donor_email === $user->email
            || ($donation->member && $donation->member->email === $user->email);

        if (! $belongsToUser) {
            abort(403, 'You can only download receipts for your own donations.');
        }

        return app(DonationReceiptService::class)->downloadReceipt($donation);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.giving.member-giving-history');
    }
}
