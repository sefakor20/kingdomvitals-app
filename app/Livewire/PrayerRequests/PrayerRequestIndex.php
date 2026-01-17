<?php

declare(strict_types=1);

namespace App\Livewire\PrayerRequests;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestPrivacy;
use App\Enums\PrayerRequestStatus;
use App\Jobs\SendPrayerChainSmsJob;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Notifications\PrayerRequestSubmittedNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PrayerRequestIndex extends Component
{
    use HasFilterableQuery;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $privacyFilter = '';

    public string $clusterFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showAnsweredModal = false;

    // Form properties
    public string $title = '';

    public string $description = '';

    public string $category = '';

    public string $privacy = 'public';

    public ?string $member_id = null;

    public ?string $cluster_id = null;

    public bool $is_anonymous = false;

    // Mark as answered form
    public string $answer_details = '';

    public ?PrayerRequest $editingPrayerRequest = null;

    public ?PrayerRequest $deletingPrayerRequest = null;

    public ?PrayerRequest $answeringPrayerRequest = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [PrayerRequest::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function prayerRequests(): Collection
    {
        $query = PrayerRequest::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['title', 'description']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyEnumFilter($query, 'privacyFilter', 'privacy');
        $this->applyEnumFilter($query, 'clusterFilter', 'cluster_id');

        return $query->with(['member', 'cluster'])
            ->orderBy('submitted_at', 'desc')
            ->get();
    }

    #[Computed]
    public function categories(): array
    {
        return PrayerRequestCategory::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return PrayerRequestStatus::cases();
    }

    #[Computed]
    public function privacyOptions(): array
    {
        return PrayerRequestPrivacy::cases();
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
    public function clusters(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [PrayerRequest::class, $this->branch]);
    }

    #[Computed]
    public function stats(): array
    {
        $all = PrayerRequest::where('branch_id', $this->branch->id);

        return [
            'total' => (clone $all)->count(),
            'open' => (clone $all)->where('status', PrayerRequestStatus::Open)->count(),
            'answered' => (clone $all)->where('status', PrayerRequestStatus::Answered)->count(),
            'answeredThisMonth' => (clone $all)
                ->where('status', PrayerRequestStatus::Answered)
                ->where('answered_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->categoryFilter)
            || $this->isFilterActive($this->statusFilter)
            || $this->isFilterActive($this->privacyFilter)
            || $this->isFilterActive($this->clusterFilter);
    }

    protected function rules(): array
    {
        $categories = collect(PrayerRequestCategory::cases())->pluck('value')->implode(',');
        $privacyOptions = collect(PrayerRequestPrivacy::cases())->pluck('value')->implode(',');

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'in:'.$categories],
            'privacy' => ['required', 'string', 'in:'.$privacyOptions],
            'is_anonymous' => ['boolean'],
            'member_id' => ['nullable', 'required_if:is_anonymous,false', 'uuid', 'exists:members,id'],
            'cluster_id' => ['nullable', 'uuid', 'exists:clusters,id'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [PrayerRequest::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [PrayerRequest::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['status'] = PrayerRequestStatus::Open;
        $validated['submitted_at'] = now();

        if ($validated['cluster_id'] === '') {
            $validated['cluster_id'] = null;
        }

        // Handle anonymous submissions
        if ($this->is_anonymous) {
            $validated['member_id'] = null;
        }

        // Remove is_anonymous from validated data as it's not a model field
        unset($validated['is_anonymous']);

        $prayerRequest = PrayerRequest::create($validated);

        // Notify cluster leaders if assigned to a cluster
        if ($prayerRequest->cluster_id) {
            $leader = $prayerRequest->cluster->leader;
            if ($leader && $leader->email) {
                // Find user associated with leader for notification
                $leaderUser = \App\Models\User::where('email', $leader->email)->first();
                if ($leaderUser) {
                    $leaderUser->notify(new PrayerRequestSubmittedNotification($prayerRequest));
                }
            }
        }

        unset($this->prayerRequests);
        unset($this->stats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('prayer-request-created');
    }

    public function edit(PrayerRequest $prayerRequest): void
    {
        $this->authorize('update', $prayerRequest);
        $this->editingPrayerRequest = $prayerRequest;
        $this->fill([
            'title' => $prayerRequest->title,
            'description' => $prayerRequest->description,
            'category' => $prayerRequest->category->value,
            'privacy' => $prayerRequest->privacy->value,
            'member_id' => $prayerRequest->member_id,
            'cluster_id' => $prayerRequest->cluster_id,
            'is_anonymous' => $prayerRequest->isAnonymous(),
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingPrayerRequest);
        $validated = $this->validate();

        if ($validated['cluster_id'] === '') {
            $validated['cluster_id'] = null;
        }

        // Handle anonymous submissions
        if ($this->is_anonymous) {
            $validated['member_id'] = null;
        }

        // Remove is_anonymous from validated data as it's not a model field
        unset($validated['is_anonymous']);

        $this->editingPrayerRequest->update($validated);

        unset($this->prayerRequests);
        unset($this->stats);

        $this->showEditModal = false;
        $this->editingPrayerRequest = null;
        $this->resetForm();
        $this->dispatch('prayer-request-updated');
    }

    public function confirmDelete(PrayerRequest $prayerRequest): void
    {
        $this->authorize('delete', $prayerRequest);
        $this->deletingPrayerRequest = $prayerRequest;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingPrayerRequest);

        $this->deletingPrayerRequest->delete();

        unset($this->prayerRequests);
        unset($this->stats);

        $this->showDeleteModal = false;
        $this->deletingPrayerRequest = null;
        $this->dispatch('prayer-request-deleted');
    }

    public function openAnsweredModal(PrayerRequest $prayerRequest): void
    {
        $this->authorize('markAnswered', $prayerRequest);
        $this->answeringPrayerRequest = $prayerRequest;
        $this->answer_details = '';
        $this->showAnsweredModal = true;
    }

    public function markAsAnswered(): void
    {
        $this->authorize('markAnswered', $this->answeringPrayerRequest);

        $this->validate([
            'answer_details' => ['nullable', 'string'],
        ]);

        $this->answeringPrayerRequest->markAsAnswered($this->answer_details ?: null);

        unset($this->prayerRequests);
        unset($this->stats);

        $this->showAnsweredModal = false;
        $this->answeringPrayerRequest = null;
        $this->answer_details = '';
        $this->dispatch('prayer-request-answered');
    }

    public function sendPrayerChain(PrayerRequest $prayerRequest): void
    {
        $this->authorize('sendPrayerChain', $prayerRequest);

        SendPrayerChainSmsJob::dispatch($prayerRequest, auth()->user());

        $this->dispatch('prayer-chain-sent');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingPrayerRequest = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPrayerRequest = null;
    }

    public function cancelAnswered(): void
    {
        $this->showAnsweredModal = false;
        $this->answeringPrayerRequest = null;
        $this->answer_details = '';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter', 'privacyFilter', 'clusterFilter']);
        unset($this->prayerRequests);
        unset($this->stats);
        unset($this->hasActiveFilters);
    }

    private function resetForm(): void
    {
        $this->reset([
            'title', 'description', 'category', 'member_id', 'cluster_id', 'is_anonymous',
        ]);
        $this->privacy = 'public';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.prayer-requests.prayer-request-index');
    }
}
