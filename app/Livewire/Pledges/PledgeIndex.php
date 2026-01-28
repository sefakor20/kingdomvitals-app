<?php

declare(strict_types=1);

namespace App\Livewire\Pledges;

use App\Enums\PledgeFrequency;
use App\Enums\PledgeStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgeCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class PledgeIndex extends Component
{
    use HasFilterableQuery;
    use WithPagination;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $statusFilter = '';

    public ?string $memberFilter = null;

    public string $campaignFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showPaymentModal = false;

    // Form properties
    public ?string $member_id = null;

    public ?string $pledge_campaign_id = null;

    public string $campaign_name = '';

    public string $amount = '';

    public string $frequency = 'one_time';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $notes = '';

    // Payment recording
    public string $paymentAmount = '';

    public ?Pledge $editingPledge = null;

    public ?Pledge $deletingPledge = null;

    public ?Pledge $recordingPaymentFor = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Pledge::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function pledges(): LengthAwarePaginator
    {
        $query = Pledge::where('branch_id', $this->branch->id);

        // Search includes relationship, so keep custom logic
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('campaign_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'memberFilter', 'member_id');
        $this->applyEnumFilter($query, 'campaignFilter', 'pledge_campaign_id');

        return $query->with(['member', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMemberFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCampaignFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function frequencies(): array
    {
        return PledgeFrequency::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return PledgeStatus::cases();
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
    public function campaigns(): Collection
    {
        return PledgeCampaign::where('branch_id', $this->branch->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function activeCampaigns(): Collection
    {
        return PledgeCampaign::where('branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Pledge::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Pledge::class, $this->branch]);
    }

    #[Computed]
    public function canRecordPayment(): bool
    {
        return auth()->user()->branchAccess()
            ->where('branch_id', $this->branch->id)
            ->whereIn('role', [
                \App\Enums\BranchRole::Admin,
                \App\Enums\BranchRole::Manager,
                \App\Enums\BranchRole::Staff,
            ])
            ->exists();
    }

    #[Computed]
    public function pledgeStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = Pledge::where('branch_id', $this->branch->id);

        // Apply same filters as main query
        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $baseQuery->where(function ($q) use ($search): void {
                $q->where('campaign_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($baseQuery, 'statusFilter', 'status');
        $this->applyEnumFilter($baseQuery, 'memberFilter', 'member_id');
        $this->applyEnumFilter($baseQuery, 'campaignFilter', 'pledge_campaign_id');

        $active = (clone $baseQuery)->where('status', PledgeStatus::Active)->count();
        $totalPledged = (clone $baseQuery)->sum('amount');
        $totalFulfilled = (clone $baseQuery)->sum('amount_fulfilled');
        $fulfillmentRate = $totalPledged > 0
            ? round(($totalFulfilled / $totalPledged) * 100, 1)
            : 0;

        return [
            'active' => $active,
            'totalPledged' => $totalPledged,
            'totalFulfilled' => $totalFulfilled,
            'fulfillmentRate' => $fulfillmentRate,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->statusFilter)
            || $this->isFilterActive($this->memberFilter)
            || $this->isFilterActive($this->campaignFilter);
    }

    protected function rules(): array
    {
        $frequencies = collect(PledgeFrequency::cases())->pluck('value')->implode(',');

        return [
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'pledge_campaign_id' => ['nullable', 'uuid', 'exists:pledge_campaigns,id'],
            'campaign_name' => ['required_without:pledge_campaign_id', 'nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'string', 'in:'.$frequencies],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Pledge::class, $this->branch]);
        $this->resetForm();
        $this->start_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Pledge::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = 'GHS';
        $validated['status'] = PledgeStatus::Active;
        $validated['amount_fulfilled'] = 0;

        // If a campaign is selected, copy its name to campaign_name for display
        if (! empty($validated['pledge_campaign_id'])) {
            $campaign = PledgeCampaign::find($validated['pledge_campaign_id']);
            if ($campaign) {
                $validated['campaign_name'] = $campaign->name;
            }
        }

        // Convert empty strings to null for nullable fields
        foreach (['end_date', 'notes', 'pledge_campaign_id', 'campaign_name'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        Pledge::create($validated);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('pledge-created');
    }

    public function edit(Pledge $pledge): void
    {
        $this->authorize('update', $pledge);
        $this->editingPledge = $pledge;
        $this->fill([
            'member_id' => $pledge->member_id,
            'pledge_campaign_id' => $pledge->pledge_campaign_id,
            'campaign_name' => $pledge->campaign_name ?? '',
            'amount' => (string) $pledge->amount,
            'frequency' => $pledge->frequency->value,
            'start_date' => $pledge->start_date?->format('Y-m-d'),
            'end_date' => $pledge->end_date?->format('Y-m-d') ?? '',
            'notes' => $pledge->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingPledge);
        $validated = $this->validate();

        // If a campaign is selected, copy its name to campaign_name for display
        if (! empty($validated['pledge_campaign_id'])) {
            $campaign = PledgeCampaign::find($validated['pledge_campaign_id']);
            if ($campaign) {
                $validated['campaign_name'] = $campaign->name;
            }
        }

        // Convert empty strings to null for nullable fields
        foreach (['end_date', 'notes', 'pledge_campaign_id', 'campaign_name'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingPledge->update($validated);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->showEditModal = false;
        $this->editingPledge = null;
        $this->resetForm();
        $this->dispatch('pledge-updated');
    }

    public function confirmDelete(Pledge $pledge): void
    {
        $this->authorize('delete', $pledge);
        $this->deletingPledge = $pledge;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingPledge);

        $this->deletingPledge->delete();

        unset($this->pledges);
        unset($this->pledgeStats);
        unset($this->campaigns);

        $this->showDeleteModal = false;
        $this->deletingPledge = null;
        $this->dispatch('pledge-deleted');
    }

    // Payment recording methods
    public function openPaymentModal(Pledge $pledge): void
    {
        $this->authorize('recordPayment', $pledge);
        $this->recordingPaymentFor = $pledge;
        $this->paymentAmount = '';
        $this->showPaymentModal = true;
    }

    public function recordPayment(): void
    {
        $this->authorize('recordPayment', $this->recordingPaymentFor);

        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $newFulfilled = (float) $this->recordingPaymentFor->amount_fulfilled + (float) $this->paymentAmount;

        $updateData = [
            'amount_fulfilled' => $newFulfilled,
        ];

        // Auto-complete if fulfilled amount meets or exceeds pledge amount
        if ($newFulfilled >= (float) $this->recordingPaymentFor->amount) {
            $updateData['status'] = PledgeStatus::Completed;
        }

        $this->recordingPaymentFor->update($updateData);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->showPaymentModal = false;
        $this->recordingPaymentFor = null;
        $this->paymentAmount = '';
        $this->dispatch('payment-recorded');
    }

    // Status change methods
    public function pausePledge(Pledge $pledge): void
    {
        $this->authorize('update', $pledge);

        if ($pledge->status !== PledgeStatus::Active) {
            return;
        }

        $pledge->update(['status' => PledgeStatus::Paused]);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->dispatch('pledge-paused');
    }

    public function resumePledge(Pledge $pledge): void
    {
        $this->authorize('update', $pledge);

        if ($pledge->status !== PledgeStatus::Paused) {
            return;
        }

        $pledge->update(['status' => PledgeStatus::Active]);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->dispatch('pledge-resumed');
    }

    public function cancelPledge(Pledge $pledge): void
    {
        $this->authorize('update', $pledge);

        if (! in_array($pledge->status, [PledgeStatus::Active, PledgeStatus::Paused])) {
            return;
        }

        $pledge->update(['status' => PledgeStatus::Cancelled]);

        unset($this->pledges);
        unset($this->pledgeStats);

        $this->dispatch('pledge-cancelled');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingPledge = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPledge = null;
    }

    public function cancelPayment(): void
    {
        $this->showPaymentModal = false;
        $this->recordingPaymentFor = null;
        $this->paymentAmount = '';
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'memberFilter', 'campaignFilter',
        ]);
        $this->resetPage();
        unset($this->pledges);
        unset($this->pledgeStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Pledge::class, $this->branch]);

        // Build query with same filters but get all records (not paginated)
        $query = Pledge::where('branch_id', $this->branch->id);

        if ($this->isFilterActive($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('campaign_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search): void {
                        $memberQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'memberFilter', 'member_id');
        $this->applyEnumFilter($query, 'campaignFilter', 'pledge_campaign_id');

        $pledges = $query->with(['member', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = sprintf(
            'pledges_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($pledges): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Member',
                'Campaign',
                'Amount',
                'Fulfilled',
                'Remaining',
                'Progress',
                'Frequency',
                'Status',
                'Start Date',
                'End Date',
                'Notes',
            ]);

            // Data rows
            foreach ($pledges as $pledge) {
                $campaignName = $pledge->campaign?->name ?? $pledge->campaign_name ?? '-';
                fputcsv($handle, [
                    $pledge->member?->fullName() ?? '-',
                    $campaignName,
                    number_format((float) $pledge->amount, 2),
                    number_format((float) $pledge->amount_fulfilled, 2),
                    number_format($pledge->remainingAmount(), 2),
                    $pledge->completionPercentage().'%',
                    str_replace('_', ' ', ucfirst($pledge->frequency->value)),
                    ucfirst($pledge->status->value),
                    $pledge->start_date?->format('Y-m-d') ?? '',
                    $pledge->end_date?->format('Y-m-d') ?? '',
                    $pledge->notes ?? '',
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
            'member_id', 'pledge_campaign_id', 'campaign_name', 'amount',
            'start_date', 'end_date', 'notes',
        ]);
        $this->frequency = 'one_time';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.pledges.pledge-index');
    }
}
