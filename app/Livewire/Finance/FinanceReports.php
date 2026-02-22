<?php

declare(strict_types=1);

namespace App\Livewire\Finance;

use App\Enums\Currency;
use App\Enums\DonationType;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PledgeStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Expense;
use App\Models\Tenant\PaymentTransaction;
use App\Models\Tenant\Pledge;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class FinanceReports extends Component
{
    public Branch $branch;

    public string $reportType = 'summary';

    public int $period = 30;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewReports', [Donation::class, $branch]);
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
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->clearComputedCaches();
        $this->dispatch('charts-updated');
    }

    public function setReportType(string $type): void
    {
        $this->reportType = $type;
        $this->clearComputedCaches();
        $this->dispatch('charts-updated');
    }

    public function applyCustomDateRange(): void
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->period = 0;
            $this->clearComputedCaches();
            $this->dispatch('charts-updated');
        }
    }

    private function clearComputedCaches(): void
    {
        unset($this->summaryStats);
        unset($this->incomeVsExpensesData);
        unset($this->donationsByTypeData);
        unset($this->donationsByPaymentMethodData);
        unset($this->expensesByCategoryData);
        unset($this->expensesByStatusData);
        unset($this->pledgeFulfillmentData);
        unset($this->topDonorsData);
        unset($this->monthlyTrendData);
        unset($this->outstandingPledgesData);
        unset($this->eventRevenueStats);
        unset($this->eventRevenueByEventData);
        unset($this->eventRevenueByPaymentMethodData);
        unset($this->eventRevenueMonthlyTrendData);
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
    public function summaryStats(): array
    {
        $totalIncome = Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        $totalExpenses = Expense::where('branch_id', $this->branch->id)
            ->where('status', ExpenseStatus::Paid)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->sum('amount');

        $pledgeStats = Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Active)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COALESCE(SUM(amount_fulfilled), 0) as fulfilled')
            ->first();

        $totalPledged = (float) $pledgeStats->total;
        $totalFulfilled = (float) $pledgeStats->fulfilled;

        return [
            'total_income' => (float) $totalIncome,
            'total_expenses' => (float) $totalExpenses,
            'net_position' => (float) $totalIncome - (float) $totalExpenses,
            'pledge_fulfillment' => $totalPledged > 0
                ? round(($totalFulfilled / $totalPledged) * 100, 1)
                : 0,
            'donation_count' => Donation::where('branch_id', $this->branch->id)
                ->whereBetween('donation_date', [$this->startDate, $this->endDate])
                ->count(),
            'expense_count' => Expense::where('branch_id', $this->branch->id)
                ->whereBetween('expense_date', [$this->startDate, $this->endDate])
                ->count(),
        ];
    }

    #[Computed]
    public function incomeVsExpensesData(): array
    {
        $months = collect();
        $current = $this->startDate->copy()->startOfMonth();
        $end = $this->endDate->copy()->endOfMonth();

        while ($current <= $end) {
            $months->push($current->copy());
            $current->addMonth();
        }

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');

            $income = Donation::where('branch_id', $this->branch->id)
                ->whereYear('donation_date', $month->year)
                ->whereMonth('donation_date', $month->month)
                ->sum('amount');

            $expenses = Expense::where('branch_id', $this->branch->id)
                ->where('status', ExpenseStatus::Paid)
                ->whereYear('expense_date', $month->year)
                ->whereMonth('expense_date', $month->month)
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
    public function donationsByTypeData(): array
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

        $results = Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$this->startDate, $this->endDate])
            ->selectRaw('donation_type, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
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
    public function donationsByPaymentMethodData(): array
    {
        $methodColors = [
            'cash' => '#22c55e',
            'check' => '#3b82f6',
            'card' => '#8b5cf6',
            'mobile_money' => '#f59e0b',
            'bank_transfer' => '#14b8a6',
        ];

        $results = Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$this->startDate, $this->endDate])
            ->selectRaw('payment_method, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->total > 0) {
                $method = $row->payment_method instanceof PaymentMethod
                    ? $row->payment_method->value
                    : $row->payment_method;
                $labels[] = ucfirst(str_replace('_', ' ', $method));
                $data[] = (float) $row->total;
                $colors[] = $methodColors[$method] ?? '#71717a';
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    #[Computed]
    public function expensesByCategoryData(): array
    {
        $categoryColors = [
            'utilities' => '#3b82f6',
            'salaries' => '#22c55e',
            'maintenance' => '#f59e0b',
            'supplies' => '#8b5cf6',
            'events' => '#ec4899',
            'missions' => '#14b8a6',
            'transport' => '#f97316',
            'other' => '#71717a',
        ];

        $results = Expense::where('branch_id', $this->branch->id)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->total > 0) {
                $category = $row->category instanceof ExpenseCategory
                    ? $row->category->value
                    : $row->category;
                $labels[] = ucfirst(str_replace('_', ' ', $category));
                $data[] = (float) $row->total;
                $colors[] = $categoryColors[$category] ?? '#71717a';
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    #[Computed]
    public function expensesByStatusData(): array
    {
        $statusColors = [
            'pending' => '#f59e0b',
            'approved' => '#3b82f6',
            'rejected' => '#ef4444',
            'paid' => '#22c55e',
        ];

        $results = Expense::where('branch_id', $this->branch->id)
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->selectRaw('status, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->count > 0) {
                $status = $row->status instanceof ExpenseStatus
                    ? $row->status->value
                    : $row->status;
                $labels[] = ucfirst($status);
                $data[] = (int) $row->count;
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
    public function pledgeFulfillmentData(): array
    {
        $activePledges = Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Active)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COALESCE(SUM(amount_fulfilled), 0) as fulfilled')
            ->first();

        $completed = Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Completed)
            ->count();

        $active = Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Active)
            ->count();

        $cancelled = Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Cancelled)
            ->count();

        return [
            'total_pledged' => (float) $activePledges->total,
            'total_fulfilled' => (float) $activePledges->fulfilled,
            'outstanding' => (float) $activePledges->total - (float) $activePledges->fulfilled,
            'fulfillment_rate' => $activePledges->total > 0
                ? round(($activePledges->fulfilled / $activePledges->total) * 100, 1)
                : 0,
            'active_count' => $active,
            'completed_count' => $completed,
            'cancelled_count' => $cancelled,
        ];
    }

    #[Computed]
    public function topDonorsData(): Collection
    {
        return Donation::where('branch_id', $this->branch->id)
            ->whereBetween('donation_date', [$this->startDate, $this->endDate])
            ->where('is_anonymous', false)
            ->whereNotNull('member_id')
            ->selectRaw('member_id, SUM(amount) as total_amount, COUNT(*) as donation_count')
            ->groupBy('member_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->with('member')
            ->get();
    }

    #[Computed]
    public function outstandingPledgesData(): Collection
    {
        return Pledge::where('branch_id', $this->branch->id)
            ->where('status', PledgeStatus::Active)
            ->whereRaw('amount > amount_fulfilled')
            ->with('member')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function monthlyTrendData(): array
    {
        $months = collect();
        $current = $this->startDate->copy()->startOfMonth();
        $end = $this->endDate->copy()->endOfMonth();

        while ($current <= $end) {
            $months->push($current->copy());
            $current->addMonth();
        }

        $labels = [];
        $netData = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');

            $income = Donation::where('branch_id', $this->branch->id)
                ->whereYear('donation_date', $month->year)
                ->whereMonth('donation_date', $month->month)
                ->sum('amount');

            $expenses = Expense::where('branch_id', $this->branch->id)
                ->where('status', ExpenseStatus::Paid)
                ->whereYear('expense_date', $month->year)
                ->whereMonth('expense_date', $month->month)
                ->sum('amount');

            $netData[] = (float) $income - (float) $expenses;
        }

        return [
            'labels' => $labels,
            'data' => $netData,
        ];
    }

    // ============================================
    // EVENT REVENUE REPORTS
    // ============================================

    #[Computed]
    public function eventRevenueStats(): array
    {
        // Total collected from event payments
        $totalCollected = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->whereBetween('paid_at', [$this->startDate, $this->endDate])
            ->sum('amount');

        $totalCount = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->whereBetween('paid_at', [$this->startDate, $this->endDate])
            ->count();

        // Pending payments (registrations requiring payment but not paid)
        $pendingPayments = EventRegistration::query()
            ->where('branch_id', $this->branch->id)
            ->where('requires_payment', true)
            ->where('is_paid', false)
            ->whereBetween('registered_at', [$this->startDate, $this->endDate])
            ->sum('price_paid');

        $pendingCount = EventRegistration::query()
            ->where('branch_id', $this->branch->id)
            ->where('requires_payment', true)
            ->where('is_paid', false)
            ->whereBetween('registered_at', [$this->startDate, $this->endDate])
            ->count();

        // Average ticket price
        $avgTicketPrice = $totalCount > 0 ? (float) $totalCollected / $totalCount : 0;

        return [
            'total_collected' => (float) $totalCollected,
            'total_registrations' => $totalCount,
            'pending_payments' => (float) $pendingPayments,
            'pending_count' => $pendingCount,
            'average_ticket_price' => $avgTicketPrice,
        ];
    }

    #[Computed]
    public function eventRevenueByEventData(): Collection
    {
        return PaymentTransaction::query()
            ->where('payment_transactions.branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('payment_transactions.status', PaymentTransactionStatus::Success)
            ->whereBetween('paid_at', [$this->startDate, $this->endDate])
            ->join('event_registrations', 'payment_transactions.event_registration_id', '=', 'event_registrations.id')
            ->join('events', 'event_registrations.event_id', '=', 'events.id')
            ->selectRaw('events.id, events.name as event_name, events.starts_at, COUNT(payment_transactions.id) as registration_count, COALESCE(SUM(payment_transactions.amount), 0) as total_revenue')
            ->groupBy('events.id', 'events.name', 'events.starts_at')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function eventRevenueByPaymentMethodData(): array
    {
        $methodColors = [
            'card' => '#8b5cf6',
            'mobile_money' => '#f59e0b',
            'bank' => '#14b8a6',
            'ussd' => '#3b82f6',
        ];

        $results = PaymentTransaction::query()
            ->where('branch_id', $this->branch->id)
            ->whereNotNull('event_registration_id')
            ->where('status', PaymentTransactionStatus::Success)
            ->whereBetween('paid_at', [$this->startDate, $this->endDate])
            ->selectRaw('channel, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            if ($row->total > 0 && $row->channel) {
                $channel = $row->channel;
                $labels[] = ucfirst(str_replace('_', ' ', $channel));
                $data[] = (float) $row->total;
                $colors[] = $methodColors[$channel] ?? '#71717a';
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    #[Computed]
    public function eventRevenueMonthlyTrendData(): array
    {
        $months = collect();
        $current = $this->startDate->copy()->startOfMonth();
        $end = $this->endDate->copy()->endOfMonth();

        while ($current <= $end) {
            $months->push($current->copy());
            $current->addMonth();
        }

        $labels = [];
        $data = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');

            $revenue = PaymentTransaction::query()
                ->where('branch_id', $this->branch->id)
                ->whereNotNull('event_registration_id')
                ->where('status', PaymentTransactionStatus::Success)
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount');

            $data[] = (float) $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewReports', [Donation::class, $this->branch]);

        $filename = "financial-report-{$this->reportType}-{$this->branch->slug}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($this->reportType === 'donations') {
                fputcsv($handle, ['Date', 'Member', 'Type', 'Amount', 'Payment Method', 'Reference']);
                $donations = Donation::where('branch_id', $this->branch->id)
                    ->whereBetween('donation_date', [$this->startDate, $this->endDate])
                    ->with('member')
                    ->orderBy('donation_date', 'desc')
                    ->get();

                foreach ($donations as $donation) {
                    fputcsv($handle, [
                        $donation->donation_date->format('Y-m-d'),
                        $donation->is_anonymous ? 'Anonymous' : ($donation->member?->fullName() ?? $donation->donor_name ?? 'N/A'),
                        ucfirst(str_replace('_', ' ', $donation->donation_type->value)),
                        number_format($donation->amount, 2),
                        ucfirst(str_replace('_', ' ', $donation->payment_method->value)),
                        $donation->reference_number ?? '',
                    ]);
                }
            } elseif ($this->reportType === 'expenses') {
                fputcsv($handle, ['Date', 'Description', 'Category', 'Amount', 'Status', 'Vendor']);
                $expenses = Expense::where('branch_id', $this->branch->id)
                    ->whereBetween('expense_date', [$this->startDate, $this->endDate])
                    ->orderBy('expense_date', 'desc')
                    ->get();

                foreach ($expenses as $expense) {
                    fputcsv($handle, [
                        $expense->expense_date->format('Y-m-d'),
                        $expense->description,
                        ucfirst(str_replace('_', ' ', $expense->category->value)),
                        number_format($expense->amount, 2),
                        ucfirst($expense->status->value),
                        $expense->vendor_name ?? '',
                    ]);
                }
            } elseif ($this->reportType === 'pledges') {
                fputcsv($handle, ['Member', 'Campaign', 'Amount Pledged', 'Amount Fulfilled', 'Remaining', 'Status']);
                $pledges = Pledge::where('branch_id', $this->branch->id)
                    ->with('member')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach ($pledges as $pledge) {
                    fputcsv($handle, [
                        $pledge->member?->fullName() ?? 'N/A',
                        $pledge->campaign_name,
                        number_format($pledge->amount, 2),
                        number_format($pledge->amount_fulfilled, 2),
                        number_format($pledge->remainingAmount(), 2),
                        ucfirst($pledge->status->value),
                    ]);
                }
            } elseif ($this->reportType === 'events') {
                fputcsv($handle, ['Date', 'Event', 'Attendee', 'Amount', 'Payment Channel', 'Reference']);
                $transactions = PaymentTransaction::query()
                    ->where('branch_id', $this->branch->id)
                    ->whereNotNull('event_registration_id')
                    ->where('status', PaymentTransactionStatus::Success)
                    ->whereBetween('paid_at', [$this->startDate, $this->endDate])
                    ->with(['eventRegistration.event'])
                    ->orderBy('paid_at', 'desc')
                    ->get();

                foreach ($transactions as $transaction) {
                    fputcsv($handle, [
                        $transaction->paid_at?->format('Y-m-d') ?? 'N/A',
                        $transaction->eventRegistration?->event?->name ?? 'N/A',
                        $transaction->eventRegistration?->attendee_name ?? 'N/A',
                        number_format($transaction->amount, 2),
                        ucfirst(str_replace('_', ' ', $transaction->channel ?? 'N/A')),
                        $transaction->paystack_reference ?? '',
                    ]);
                }
            } else {
                // Summary export
                fputcsv($handle, ['Metric', 'Value']);
                $stats = $this->summaryStats;
                $currencySymbol = tenant()->getCurrencySymbol();
                fputcsv($handle, ['Total Income', $currencySymbol.number_format($stats['total_income'], 2)]);
                fputcsv($handle, ['Total Expenses', $currencySymbol.number_format($stats['total_expenses'], 2)]);
                fputcsv($handle, ['Net Position', $currencySymbol.number_format($stats['net_position'], 2)]);
                fputcsv($handle, ['Pledge Fulfillment Rate', $stats['pledge_fulfillment'].'%']);
                fputcsv($handle, ['Donation Count', $stats['donation_count']]);
                fputcsv($handle, ['Expense Count', $stats['expense_count']]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.finance.finance-reports');
    }
}
