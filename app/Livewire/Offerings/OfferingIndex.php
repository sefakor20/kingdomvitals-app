<?php

declare(strict_types=1);

namespace App\Livewire\Offerings;

use App\Enums\DonationType;
use App\Enums\PaymentMethod;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\DonationReceiptService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class OfferingIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // View mode: 'list' or 'summary'
    public string $viewMode = 'list';

    // Search and filters
    public string $search = '';

    public string $serviceFilter = '';

    public string $paymentMethodFilter = '';

    public ?string $memberFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public ?string $member_id = null;

    public ?string $service_id = null;

    public string $amount = '';

    public string $payment_method = 'cash';

    public ?string $donation_date = null;

    public string $donor_name = '';

    public string $reference_number = '';

    public bool $is_anonymous = false;

    public string $notes = '';

    public ?Donation $editingOffering = null;

    public ?Donation $deletingOffering = null;

    /** @var array<string> */
    public array $selectedOfferings = [];

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Donation::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function offerings(): Collection
    {
        $query = Donation::where('branch_id', $this->branch->id)
            ->where('donation_type', DonationType::Offering);

        // Search includes relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('donor_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'serviceFilter', 'service_id');
        $this->applyEnumFilter($query, 'paymentMethodFilter', 'payment_method');

        // Custom member filter with anonymous support
        if ($this->memberFilter !== null) {
            if ($this->memberFilter === 'anonymous') {
                $query->where('is_anonymous', true);
            } else {
                $query->where('member_id', $this->memberFilter);
            }
        }

        $this->applyDateRange($query, 'donation_date');

        return $query->with(['member', 'service', 'recorder'])
            ->orderBy('donation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function serviceSummary(): Collection
    {
        $query = Donation::where('branch_id', $this->branch->id)
            ->where('donation_type', DonationType::Offering);

        if ($this->serviceFilter !== '' && $this->serviceFilter !== '0') {
            $query->where('service_id', $this->serviceFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('donation_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('donation_date', '<=', $this->dateTo);
        }

        return $query->selectRaw('service_id, donation_date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('service_id', 'donation_date')
            ->orderBy('donation_date', 'desc')
            ->get()
            ->map(function ($item) {
                $item->service = $item->service_id ? Service::find($item->service_id) : null;

                return $item;
            });
    }

    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Donation::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Donation::class, $this->branch]);
    }

    #[Computed]
    public function selectedOfferingsCount(): int
    {
        return count($this->selectedOfferings);
    }

    #[Computed]
    public function canSendReceipts(): bool
    {
        $firstOffering = Donation::where('branch_id', $this->branch->id)
            ->where('donation_type', DonationType::Offering)
            ->first();

        return $firstOffering && auth()->user()->can('sendReceipt', $firstOffering);
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = Donation::where('branch_id', $this->branch->id)
            ->where('donation_type', DonationType::Offering);

        $total = (clone $baseQuery)->sum('amount');
        $count = (clone $baseQuery)->count();

        $thisWeek = (clone $baseQuery)
            ->whereBetween('donation_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');

        $thisMonth = (clone $baseQuery)
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');

        return [
            'total' => $total,
            'count' => $count,
            'thisWeek' => $thisWeek,
            'thisMonth' => $thisMonth,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->serviceFilter)
            || $this->isFilterActive($this->paymentMethodFilter)
            || $this->isFilterActive($this->memberFilter)
            || $this->isFilterActive($this->dateFrom)
            || $this->isFilterActive($this->dateTo);
    }

    protected function rules(): array
    {
        $paymentMethods = collect(PaymentMethod::cases())->pluck('value')->implode(',');

        return [
            'member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:'.$paymentMethods],
            'donation_date' => ['required', 'date'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'is_anonymous' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function create(): void
    {
        $this->authorize('create', [Donation::class, $this->branch]);
        $this->resetForm();
        $this->donation_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Donation::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['donation_type'] = DonationType::Offering;
        $validated['currency'] = 'GHS';

        // Convert empty strings to null for nullable fields
        $nullableFields = ['member_id', 'service_id', 'donor_name', 'reference_number', 'notes'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // If anonymous, clear member_id and donor_name
        if ($validated['is_anonymous']) {
            $validated['member_id'] = null;
            $validated['donor_name'] = null;
        }

        Donation::create($validated);

        unset($this->offerings);
        unset($this->serviceSummary);
        unset($this->stats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('offering-created');
    }

    public function edit(Donation $donation): void
    {
        $this->authorize('update', $donation);
        $this->editingOffering = $donation;
        $this->fill([
            'member_id' => $donation->member_id,
            'service_id' => $donation->service_id,
            'amount' => (string) $donation->amount,
            'payment_method' => $donation->payment_method->value,
            'donation_date' => $donation->donation_date?->format('Y-m-d'),
            'donor_name' => $donation->donor_name ?? '',
            'reference_number' => $donation->reference_number ?? '',
            'is_anonymous' => $donation->is_anonymous,
            'notes' => $donation->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingOffering);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['member_id', 'service_id', 'donor_name', 'reference_number', 'notes'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // If anonymous, clear member_id and donor_name
        if ($validated['is_anonymous']) {
            $validated['member_id'] = null;
            $validated['donor_name'] = null;
        }

        $this->editingOffering->update($validated);

        unset($this->offerings);
        unset($this->serviceSummary);
        unset($this->stats);

        $this->showEditModal = false;
        $this->editingOffering = null;
        $this->resetForm();
        $this->dispatch('offering-updated');
    }

    public function confirmDelete(Donation $donation): void
    {
        $this->authorize('delete', $donation);
        $this->deletingOffering = $donation;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingOffering);

        $this->deletingOffering->delete();

        unset($this->offerings);
        unset($this->serviceSummary);
        unset($this->stats);

        $this->showDeleteModal = false;
        $this->deletingOffering = null;
        $this->dispatch('offering-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingOffering = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingOffering = null;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'serviceFilter', 'paymentMethodFilter',
            'memberFilter', 'dateFrom', 'dateTo',
        ]);
        unset($this->offerings);
        unset($this->serviceSummary);
        unset($this->stats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Donation::class, $this->branch]);

        $offerings = $this->offerings;

        $filename = sprintf(
            'offerings_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($offerings): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Member/Donor',
                'Amount',
                'Currency',
                'Payment Method',
                'Reference',
                'Service',
                'Anonymous',
                'Notes',
            ]);

            // Data rows
            foreach ($offerings as $offering) {
                $donorName = $offering->is_anonymous
                    ? 'Anonymous'
                    : ($offering->member?->fullName() ?? $offering->donor_name ?? '-');

                fputcsv($handle, [
                    $offering->donation_date?->format('Y-m-d') ?? '',
                    $donorName,
                    number_format((float) $offering->amount, 2),
                    $offering->currency,
                    str_replace('_', ' ', ucfirst($offering->payment_method->value)),
                    $offering->reference_number ?? '',
                    $offering->service?->name ?? '',
                    $offering->is_anonymous ? 'Yes' : 'No',
                    $offering->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'member_id', 'service_id', 'amount', 'donor_name',
            'reference_number', 'is_anonymous', 'notes', 'donation_date',
        ]);
        $this->payment_method = 'cash';
        $this->resetValidation();
    }

    // Receipt Methods

    public function downloadReceipt(Donation $donation): StreamedResponse
    {
        $this->authorize('generateReceipt', $donation);

        return app(DonationReceiptService::class)->downloadReceipt($donation);
    }

    public function emailReceipt(Donation $donation): void
    {
        $this->authorize('sendReceipt', $donation);

        if (app(DonationReceiptService::class)->emailReceipt($donation)) {
            unset($this->offerings);
            $this->dispatch('receipt-sent');
        } else {
            $this->dispatch('receipt-send-failed');
        }
    }

    public function bulkDownloadReceipts(): StreamedResponse
    {
        $offerings = Donation::whereIn('id', $this->selectedOfferings)->get();

        foreach ($offerings as $offering) {
            $this->authorize('generateReceipt', $offering);
        }

        return app(DonationReceiptService::class)->bulkDownloadReceipts($offerings);
    }

    public function bulkEmailReceipts(): void
    {
        $offerings = Donation::whereIn('id', $this->selectedOfferings)->get();

        foreach ($offerings as $offering) {
            $this->authorize('sendReceipt', $offering);
        }

        $result = app(DonationReceiptService::class)->bulkEmailReceipts($offerings);
        $this->selectedOfferings = [];
        unset($this->offerings);
        unset($this->selectedOfferingsCount);

        $this->dispatch('bulk-receipts-sent', sent: $result['sent'], skipped: $result['skipped']);
    }

    public function toggleOfferingSelection(string $offeringId): void
    {
        if (in_array($offeringId, $this->selectedOfferings)) {
            $this->selectedOfferings = array_values(array_diff($this->selectedOfferings, [$offeringId]));
        } else {
            $this->selectedOfferings[] = $offeringId;
        }
        unset($this->selectedOfferingsCount);
    }

    public function selectAllOfferings(): void
    {
        $this->selectedOfferings = $this->offerings->pluck('id')->toArray();
        unset($this->selectedOfferingsCount);
    }

    public function deselectAllOfferings(): void
    {
        $this->selectedOfferings = [];
        unset($this->selectedOfferingsCount);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.offerings.offering-index');
    }
}
