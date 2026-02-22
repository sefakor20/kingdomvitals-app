<?php

declare(strict_types=1);

namespace App\Livewire\Finance;

use App\Enums\Currency;
use App\Enums\DonationType;
use App\Enums\ExpenseStatus;
use App\Enums\MembershipStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PledgeStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\PaymentTransaction;
use App\Models\Tenant\Pledge;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FinanceDashboard extends Component
{
    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', [Donation::class, $branch]);
        $this->branch = $branch;
    }

    // ============================================
    // CURRENCY
    // ============================================

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    // ============================================
    // EXECUTIVE SUMMARY STATS
    // ============================================

    #[Computed]
    public function monthlyStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currencyCode = tenant()->getCurrencyCode();

        $income = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', $currentMonth)
            ->whereYear('donation_date', $currentYear)
            ->sum('amount');

        $incomeCount = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', $currentMonth)
            ->whereYear('donation_date', $currentYear)
            ->count();

        $expenses = Expense::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->where('status', ExpenseStatus::Paid)
            ->whereMonth('expense_date', $currentMonth)
            ->whereYear('expense_date', $currentYear)
            ->sum('amount');

        $expensesCount = Expense::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('expense_date', $currentMonth)
            ->whereYear('expense_date', $currentYear)
            ->count();

        return [
            'income' => (float) $income,
            'income_count' => $incomeCount,
            'expenses' => (float) $expenses,
            'expenses_count' => $expensesCount,
            'net_position' => (float) $income - (float) $expenses,
        ];
    }

    #[Computed]
    public function yearToDateStats(): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;
        $currentDayOfYear = now()->dayOfYear;
        $currencyCode = tenant()->getCurrencyCode();

        // Current year YTD
        $incomeYtd = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereYear('donation_date', $currentYear)
            ->where('donation_date', '<=', now())
            ->sum('amount');

        $expensesYtd = Expense::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->where('status', ExpenseStatus::Paid)
            ->whereYear('expense_date', $currentYear)
            ->where('expense_date', '<=', now())
            ->sum('amount');

        $donationCountYtd = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereYear('donation_date', $currentYear)
            ->where('donation_date', '<=', now())
            ->count();

        // Previous year same period (for YoY comparison)
        $samePeriodLastYear = Carbon::create($previousYear, 1, 1)->addDays($currentDayOfYear - 1);

        $incomeLastYear = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereYear('donation_date', $previousYear)
            ->where('donation_date', '<=', $samePeriodLastYear)
            ->sum('amount');

        $expensesLastYear = Expense::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->where('status', ExpenseStatus::Paid)
            ->whereYear('expense_date', $previousYear)
            ->where('expense_date', '<=', $samePeriodLastYear)
            ->sum('amount');

        $donationCountLastYear = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereYear('donation_date', $previousYear)
            ->where('donation_date', '<=', $samePeriodLastYear)
            ->count();

        // Calculate growth percentages
        $incomeGrowth = $incomeLastYear > 0
            ? round((($incomeYtd - $incomeLastYear) / $incomeLastYear) * 100, 1)
            : ($incomeYtd > 0 ? 100 : 0);

        $expensesGrowth = $expensesLastYear > 0
            ? round((($expensesYtd - $expensesLastYear) / $expensesLastYear) * 100, 1)
            : ($expensesYtd > 0 ? 100 : 0);

        $donationCountGrowth = $donationCountLastYear > 0
            ? round((($donationCountYtd - $donationCountLastYear) / $donationCountLastYear) * 100, 1)
            : ($donationCountYtd > 0 ? 100 : 0);

        return [
            'income' => (float) $incomeYtd,
            'income_last_year' => (float) $incomeLastYear,
            'income_growth_percent' => $incomeGrowth,
            'expenses' => (float) $expensesYtd,
            'expenses_last_year' => (float) $expensesLastYear,
            'expenses_growth_percent' => $expensesGrowth,
            'donation_count' => $donationCountYtd,
            'donation_count_last_year' => $donationCountLastYear,
            'donation_count_growth_percent' => $donationCountGrowth,
        ];
    }

    #[Computed]
    public function outstandingPledgesTotal(): float
    {
        $currencyCode = tenant()->getCurrencyCode();

        $pledges = Pledge::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->where('status', PledgeStatus::Active)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COALESCE(SUM(amount_fulfilled), 0) as fulfilled')
            ->first();

        return (float) $pledges->total - (float) $pledges->fulfilled;
    }

    // ============================================
    // EVENT REVENUE STATISTICS
    // ============================================

    #[Computed]
    public function eventRevenueStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currencyCode = tenant()->getCurrencyCode();

        // Event revenue this month
        $eventRevenue = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->where('currency', $currencyCode)
            ->whereMonth('paid_at', $currentMonth)
            ->whereYear('paid_at', $currentYear)
            ->sum('amount');

        $eventRevenueCount = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->where('currency', $currencyCode)
            ->whereMonth('paid_at', $currentMonth)
            ->whereYear('paid_at', $currentYear)
            ->count();

        // Event revenue YTD
        $eventRevenueYtd = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->where('currency', $currencyCode)
            ->whereYear('paid_at', $currentYear)
            ->where('paid_at', '<=', now())
            ->sum('amount');

        // Pending event payments
        $pendingEventPayments = EventRegistration::query()
            ->where('branch_id', $this->branch->id)
            ->where('requires_payment', true)
            ->where('is_paid', false)
            ->whereHas('event', function ($query) use ($currencyCode): void {
                $query->where('currency', $currencyCode);
            })
            ->sum('price_paid');

        return [
            'monthly_revenue' => (float) $eventRevenue,
            'monthly_count' => $eventRevenueCount,
            'ytd_revenue' => (float) $eventRevenueYtd,
            'pending_payments' => (float) $pendingEventPayments,
        ];
    }

    #[Computed]
    public function eventRevenueChartData(): array
    {
        $labels = [];
        $data = [];
        $currencyCode = tenant()->getCurrencyCode();

        // Last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');

            $revenue = PaymentTransaction::query()
                ->where('branch_id', $this->branch->id)
                ->whereNotNull('event_registration_id')
                ->where('status', PaymentTransactionStatus::Success)
                ->where('currency', $currencyCode)
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');

            $data[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    // ============================================
    // MEMBER GIVING STATISTICS
    // ============================================

    #[Computed]
    public function memberGivingStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currencyCode = tenant()->getCurrencyCode();

        // Average donation this month
        $averageDonation = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', $currentMonth)
            ->whereYear('donation_date', $currentYear)
            ->avg('amount');

        // Unique donors this month (member-based)
        $uniqueDonors = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', $currentMonth)
            ->whereYear('donation_date', $currentYear)
            ->whereNotNull('member_id')
            ->distinct('member_id')
            ->count('member_id');

        // Active members count
        $activeMembers = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->count();

        // Giving percentage
        $givingPercentage = $activeMembers > 0
            ? round(($uniqueDonors / $activeMembers) * 100, 1)
            : 0;

        // First-time donors this month
        $firstTimeDonors = $this->calculateFirstTimeDonors();

        return [
            'average_donation' => (float) ($averageDonation ?? 0),
            'unique_donors' => $uniqueDonors,
            'active_members' => $activeMembers,
            'giving_percentage' => $givingPercentage,
            'first_time_donors' => $firstTimeDonors,
        ];
    }

    private function calculateFirstTimeDonors(): int
    {
        $startOfMonth = now()->startOfMonth();
        $currencyCode = tenant()->getCurrencyCode();

        // Get unique member IDs who donated this month (2 queries instead of N+1)
        $donorsThisMonth = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->whereNotNull('member_id')
            ->distinct()
            ->pluck('member_id');

        if ($donorsThisMonth->isEmpty()) {
            return 0;
        }

        // Get member IDs who had donations BEFORE this month
        $previousDonors = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereIn('member_id', $donorsThisMonth)
            ->where('donation_date', '<', $startOfMonth)
            ->distinct()
            ->pluck('member_id');

        // First-timers = this month donors - previous donors
        return $donorsThisMonth->diff($previousDonors)->count();
    }

    // ============================================
    // CHART DATA
    // ============================================

    #[Computed]
    public function monthlyIncomeChartData(): array
    {
        $labels = [];
        $currentYearData = [];
        $previousYearData = [];
        $currencyCode = tenant()->getCurrencyCode();

        // Last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');

            $income = Donation::where('branch_id', $this->branch->id)
                ->where('currency', $currencyCode)
                ->whereYear('donation_date', $date->year)
                ->whereMonth('donation_date', $date->month)
                ->sum('amount');

            $currentYearData[] = (float) $income;

            // Same month previous year
            $prevDate = $date->copy()->subYear();
            $prevIncome = Donation::where('branch_id', $this->branch->id)
                ->where('currency', $currencyCode)
                ->whereYear('donation_date', $prevDate->year)
                ->whereMonth('donation_date', $prevDate->month)
                ->sum('amount');

            $previousYearData[] = (float) $prevIncome;
        }

        return [
            'labels' => $labels,
            'current_year' => $currentYearData,
            'previous_year' => $previousYearData,
        ];
    }

    #[Computed]
    public function donationTypesChartData(): array
    {
        $typeColors = [
            'tithe' => '#22c55e',
            'offering' => '#3b82f6',
            'building_fund' => '#8b5cf6',
            'missions' => '#f59e0b',
            'special' => '#ec4899',
            'welfare' => '#14b8a6',
            'other' => '#71717a',
        ];

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currencyCode = tenant()->getCurrencyCode();

        $results = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereMonth('donation_date', $currentMonth)
            ->whereYear('donation_date', $currentYear)
            ->selectRaw('donation_type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('donation_type')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->total > 0) {
                $type = $row->donation_type instanceof DonationType
                    ? $row->donation_type->value
                    : $row->donation_type;
                $labels[] = ucfirst(str_replace('_', ' ', $type));
                $data[] = (float) $row->total;
                $colors[] = $typeColors[$type] ?? '#71717a';
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    #[Computed]
    public function incomeVsExpensesChartData(): array
    {
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $currencyCode = tenant()->getCurrencyCode();

        // Last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $income = Donation::where('branch_id', $this->branch->id)
                ->where('currency', $currencyCode)
                ->whereYear('donation_date', $date->year)
                ->whereMonth('donation_date', $date->month)
                ->sum('amount');

            $expenses = Expense::where('branch_id', $this->branch->id)
                ->where('currency', $currencyCode)
                ->where('status', ExpenseStatus::Paid)
                ->whereYear('expense_date', $date->year)
                ->whereMonth('expense_date', $date->month)
                ->sum('amount');

            $incomeData[] = (float) $income;
            $expenseData[] = (float) $expenses;
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expenses' => $expenseData,
        ];
    }

    #[Computed]
    public function donationGrowthChartData(): array
    {
        $labels = [];
        $data = [];
        $cumulative = 0;
        $currencyCode = tenant()->getCurrencyCode();

        // Last 12 months cumulative
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');

            $income = Donation::where('branch_id', $this->branch->id)
                ->where('currency', $currencyCode)
                ->whereYear('donation_date', $date->year)
                ->whereMonth('donation_date', $date->month)
                ->sum('amount');

            $cumulative += (float) $income;
            $data[] = $cumulative;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function topDonorsTierData(): array
    {
        $currentYear = now()->year;
        $currencyCode = tenant()->getCurrencyCode();

        // Get all donors with their total donations this year
        $donors = Donation::where('branch_id', $this->branch->id)
            ->where('currency', $currencyCode)
            ->whereYear('donation_date', $currentYear)
            ->whereNotNull('member_id')
            ->where('is_anonymous', false)
            ->selectRaw('member_id, SUM(amount) as total_amount')
            ->groupBy('member_id')
            ->orderByDesc('total_amount')
            ->get();

        $totalDonors = $donors->count();

        if ($totalDonors === 0) {
            return [
                'tiers' => ['Top 10%', 'Top 25%', 'Top 50%', 'Bottom 50%'],
                'amounts' => [0, 0, 0, 0],
                'counts' => [0, 0, 0, 0],
            ];
        }

        $top10Percent = max(1, (int) ceil($totalDonors * 0.1));
        $top25Percent = max(1, (int) ceil($totalDonors * 0.25));
        $top50Percent = max(1, (int) ceil($totalDonors * 0.5));

        $top10Amount = $donors->take($top10Percent)->sum('total_amount');
        $top25Amount = $donors->take($top25Percent)->sum('total_amount');
        $top50Amount = $donors->take($top50Percent)->sum('total_amount');
        $totalAmount = $donors->sum('total_amount');

        return [
            'tiers' => ['Top 10%', 'Top 11-25%', 'Top 26-50%', 'Bottom 50%'],
            'amounts' => [
                (float) $top10Amount,
                (float) ($top25Amount - $top10Amount),
                (float) ($top50Amount - $top25Amount),
                (float) ($totalAmount - $top50Amount),
            ],
            'counts' => [
                $top10Percent,
                $top25Percent - $top10Percent,
                $top50Percent - $top25Percent,
                $totalDonors - $top50Percent,
            ],
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.finance.finance-dashboard');
    }
}
