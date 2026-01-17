<?php

declare(strict_types=1);

namespace App\Livewire\Donations;

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
class DonationIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $typeFilter = '';

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

    public string $donation_type = 'offering';

    public string $payment_method = 'cash';

    public ?string $donation_date = null;

    public string $donor_name = '';

    public string $reference_number = '';

    public bool $is_anonymous = false;

    public string $notes = '';

    public ?Donation $editingDonation = null;

    public ?Donation $deletingDonation = null;

    /** @var array<string> */
    public array $selectedDonations = [];

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Donation::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function donations(): Collection
    {
        $query = Donation::where('branch_id', $this->branch->id);

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

        $this->applyEnumFilter($query, 'typeFilter', 'donation_type');
        $this->applyEnumFilter($query, 'paymentMethodFilter', 'payment_method');
        $this->applyDateRange($query, 'donation_date');

        // Custom member filter with anonymous support
        if ($this->memberFilter !== null) {
            if ($this->memberFilter === 'anonymous') {
                $query->where('is_anonymous', true);
            } else {
                $query->where('member_id', $this->memberFilter);
            }
        }

        return $query->with(['member', 'service', 'recorder'])
            ->orderBy('donation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function donationTypes(): array
    {
        return DonationType::cases();
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
    public function selectedDonationsCount(): int
    {
        return count($this->selectedDonations);
    }

    #[Computed]
    public function canSendReceipts(): bool
    {
        $firstDonation = Donation::where('branch_id', $this->branch->id)->first();

        return $firstDonation && auth()->user()->can('sendReceipt', $firstDonation);
    }

    #[Computed]
    public function donationStats(): array
    {
        $donations = $this->donations;
        $total = $donations->sum('amount');
        $count = $donations->count();

        $thisMonthDonations = $donations->filter(function ($donation): bool {
            return $donation->donation_date &&
                $donation->donation_date->isCurrentMonth();
        });
        $thisMonth = $thisMonthDonations->sum('amount');

        $tithes = $donations->where('donation_type', DonationType::Tithe)->sum('amount');
        $offerings = $donations->where('donation_type', DonationType::Offering)->sum('amount');

        return [
            'total' => $total,
            'count' => $count,
            'thisMonth' => $thisMonth,
            'tithes' => $tithes,
            'offerings' => $offerings,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->typeFilter)
            || $this->isFilterActive($this->paymentMethodFilter)
            || $this->isFilterActive($this->memberFilter)
            || $this->isFilterActive($this->dateFrom)
            || $this->isFilterActive($this->dateTo);
    }

    protected function rules(): array
    {
        $donationTypes = collect(DonationType::cases())->pluck('value')->implode(',');
        $paymentMethods = collect(PaymentMethod::cases())->pluck('value')->implode(',');

        return [
            'member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'donation_type' => ['required', 'string', 'in:'.$donationTypes],
            'payment_method' => ['required', 'string', 'in:'.$paymentMethods],
            'donation_date' => ['required', 'date'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'is_anonymous' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
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

        unset($this->donations);
        unset($this->donationStats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('donation-created');
    }

    public function edit(Donation $donation): void
    {
        $this->authorize('update', $donation);
        $this->editingDonation = $donation;
        $this->fill([
            'member_id' => $donation->member_id,
            'service_id' => $donation->service_id,
            'amount' => (string) $donation->amount,
            'donation_type' => $donation->donation_type->value,
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
        $this->authorize('update', $this->editingDonation);
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

        $this->editingDonation->update($validated);

        unset($this->donations);
        unset($this->donationStats);

        $this->showEditModal = false;
        $this->editingDonation = null;
        $this->resetForm();
        $this->dispatch('donation-updated');
    }

    public function confirmDelete(Donation $donation): void
    {
        $this->authorize('delete', $donation);
        $this->deletingDonation = $donation;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingDonation);

        $this->deletingDonation->delete();

        unset($this->donations);
        unset($this->donationStats);

        $this->showDeleteModal = false;
        $this->deletingDonation = null;
        $this->dispatch('donation-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingDonation = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingDonation = null;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'typeFilter', 'paymentMethodFilter',
            'memberFilter', 'dateFrom', 'dateTo',
        ]);
        unset($this->donations);
        unset($this->donationStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Donation::class, $this->branch]);

        $donations = $this->donations;

        $filename = sprintf(
            'donations_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($donations): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Date',
                'Member/Donor',
                'Type',
                'Amount',
                'Currency',
                'Payment Method',
                'Reference',
                'Service',
                'Anonymous',
                'Notes',
            ]);

            // Data rows
            foreach ($donations as $donation) {
                $donorName = $donation->is_anonymous
                    ? 'Anonymous'
                    : ($donation->member?->fullName() ?? $donation->donor_name ?? '-');

                fputcsv($handle, [
                    $donation->donation_date?->format('Y-m-d') ?? '',
                    $donorName,
                    str_replace('_', ' ', ucfirst($donation->donation_type->value)),
                    number_format((float) $donation->amount, 2),
                    $donation->currency,
                    str_replace('_', ' ', ucfirst($donation->payment_method->value)),
                    $donation->reference_number ?? '',
                    $donation->service?->name ?? '',
                    $donation->is_anonymous ? 'Yes' : 'No',
                    $donation->notes ?? '',
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
        $this->donation_type = 'offering';
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
            unset($this->donations);
            $this->dispatch('receipt-sent');
        } else {
            $this->dispatch('receipt-send-failed');
        }
    }

    public function bulkDownloadReceipts(): StreamedResponse
    {
        $donations = Donation::whereIn('id', $this->selectedDonations)->get();

        foreach ($donations as $donation) {
            $this->authorize('generateReceipt', $donation);
        }

        return app(DonationReceiptService::class)->bulkDownloadReceipts($donations);
    }

    public function bulkEmailReceipts(): void
    {
        $donations = Donation::whereIn('id', $this->selectedDonations)->get();

        foreach ($donations as $donation) {
            $this->authorize('sendReceipt', $donation);
        }

        $result = app(DonationReceiptService::class)->bulkEmailReceipts($donations);
        $this->selectedDonations = [];
        unset($this->donations);
        unset($this->selectedDonationsCount);

        $this->dispatch('bulk-receipts-sent', sent: $result['sent'], skipped: $result['skipped']);
    }

    public function toggleDonationSelection(string $donationId): void
    {
        if (in_array($donationId, $this->selectedDonations)) {
            $this->selectedDonations = array_values(array_diff($this->selectedDonations, [$donationId]));
        } else {
            $this->selectedDonations[] = $donationId;
        }
        unset($this->selectedDonationsCount);
    }

    public function selectAllDonations(): void
    {
        $this->selectedDonations = $this->donations->pluck('id')->toArray();
        unset($this->selectedDonationsCount);
    }

    public function deselectAllDonations(): void
    {
        $this->selectedDonations = [];
        unset($this->selectedDonationsCount);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.donations.donation-index');
    }
}
