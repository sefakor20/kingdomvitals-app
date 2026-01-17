<?php

declare(strict_types=1);

namespace App\Livewire\Pledges;

use App\Enums\CampaignCategory;
use App\Enums\CampaignStatus;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PledgeCampaign;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class CampaignIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $statusFilter = '';

    public string $categoryFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showDetailModal = false;

    // Form properties
    public string $name = '';

    public string $description = '';

    public string $category = '';

    public string $goal_amount = '';

    public string $goal_participants = '';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?PledgeCampaign $editingCampaign = null;

    public ?PledgeCampaign $deletingCampaign = null;

    public ?PledgeCampaign $viewingCampaign = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [PledgeCampaign::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function campaigns(): Collection
    {
        $query = PledgeCampaign::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'description']);
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'categoryFilter', 'category');

        return $query->withCount('pledges')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function categories(): array
    {
        return CampaignCategory::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return CampaignStatus::cases();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [PledgeCampaign::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [PledgeCampaign::class, $this->branch]);
    }

    #[Computed]
    public function campaignStats(): array
    {
        $campaigns = $this->campaigns;

        $total = $campaigns->count();
        $active = $campaigns->where('status', CampaignStatus::Active)->count();
        $totalGoal = $campaigns->sum('goal_amount');

        $totalPledged = 0;
        $totalFulfilled = 0;
        foreach ($campaigns as $campaign) {
            $totalPledged += $campaign->totalPledged();
            $totalFulfilled += $campaign->totalFulfilled();
        }

        return [
            'total' => $total,
            'active' => $active,
            'totalGoal' => $totalGoal,
            'totalPledged' => $totalPledged,
            'totalFulfilled' => $totalFulfilled,
            'overallProgress' => $totalGoal > 0 ? round(($totalPledged / $totalGoal) * 100, 1) : 0,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->statusFilter)
            || $this->isFilterActive($this->categoryFilter);
    }

    protected function rules(): array
    {
        $categories = collect(CampaignCategory::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'in:'.$categories],
            'goal_amount' => ['nullable', 'numeric', 'min:0'],
            'goal_participants' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [PledgeCampaign::class, $this->branch]);
        $this->resetForm();
        $this->start_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [PledgeCampaign::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = 'GHS';
        $validated['status'] = CampaignStatus::Active;

        // Convert empty strings to null for nullable fields
        foreach (['description', 'category', 'goal_amount', 'goal_participants', 'end_date'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        PledgeCampaign::create($validated);

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('campaign-created');
    }

    public function edit(PledgeCampaign $campaign): void
    {
        $this->authorize('update', $campaign);
        $this->editingCampaign = $campaign;
        $this->fill([
            'name' => $campaign->name,
            'description' => $campaign->description ?? '',
            'category' => $campaign->category?->value ?? '',
            'goal_amount' => $campaign->goal_amount ? (string) $campaign->goal_amount : '',
            'goal_participants' => $campaign->goal_participants ? (string) $campaign->goal_participants : '',
            'start_date' => $campaign->start_date?->format('Y-m-d'),
            'end_date' => $campaign->end_date?->format('Y-m-d') ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingCampaign);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        foreach (['description', 'category', 'goal_amount', 'goal_participants', 'end_date'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingCampaign->update($validated);

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->showEditModal = false;
        $this->editingCampaign = null;
        $this->resetForm();
        $this->dispatch('campaign-updated');
    }

    public function confirmDelete(PledgeCampaign $campaign): void
    {
        $this->authorize('delete', $campaign);
        $this->deletingCampaign = $campaign;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingCampaign);

        $this->deletingCampaign->delete();

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->showDeleteModal = false;
        $this->deletingCampaign = null;
        $this->dispatch('campaign-deleted');
    }

    public function viewDetails(PledgeCampaign $campaign): void
    {
        $this->authorize('view', $campaign);
        $this->viewingCampaign = $campaign->load('pledges.member');
        $this->showDetailModal = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailModal = false;
        $this->viewingCampaign = null;
    }

    public function activateCampaign(PledgeCampaign $campaign): void
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== CampaignStatus::Draft) {
            return;
        }

        $campaign->update(['status' => CampaignStatus::Active]);

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->dispatch('campaign-activated');
    }

    public function completeCampaign(PledgeCampaign $campaign): void
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== CampaignStatus::Active) {
            return;
        }

        $campaign->update(['status' => CampaignStatus::Completed]);

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->dispatch('campaign-completed');
    }

    public function cancelCampaign(PledgeCampaign $campaign): void
    {
        $this->authorize('update', $campaign);

        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Active])) {
            return;
        }

        $campaign->update(['status' => CampaignStatus::Cancelled]);

        unset($this->campaigns);
        unset($this->campaignStats);

        $this->dispatch('campaign-cancelled');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingCampaign = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingCampaign = null;
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        unset($this->campaigns);
        unset($this->campaignStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [PledgeCampaign::class, $this->branch]);

        $campaigns = $this->campaigns;

        $filename = sprintf(
            'campaigns_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($campaigns): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'Name',
                'Category',
                'Goal Amount',
                'Goal Participants',
                'Total Pledged',
                'Total Fulfilled',
                'Participants',
                'Amount Progress',
                'Status',
                'Start Date',
                'End Date',
                'Description',
            ]);

            // Data rows
            foreach ($campaigns as $campaign) {
                fputcsv($handle, [
                    $campaign->name,
                    $campaign->category?->value ? str_replace('_', ' ', ucfirst($campaign->category->value)) : '-',
                    $campaign->goal_amount ? number_format((float) $campaign->goal_amount, 2) : '-',
                    $campaign->goal_participants ?? '-',
                    number_format($campaign->totalPledged(), 2),
                    number_format($campaign->totalFulfilled(), 2),
                    $campaign->participantCount(),
                    $campaign->amountProgress().'%',
                    ucfirst($campaign->status->value),
                    $campaign->start_date?->format('Y-m-d') ?? '',
                    $campaign->end_date?->format('Y-m-d') ?? '',
                    $campaign->description ?? '',
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
            'name', 'description', 'category', 'goal_amount',
            'goal_participants', 'start_date', 'end_date',
        ]);
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.pledges.campaign-index');
    }
}
