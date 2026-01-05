<?php

declare(strict_types=1);

namespace App\Livewire\PrayerRequests;

use App\Enums\PrayerRequestCategory;
use App\Enums\PrayerRequestPrivacy;
use App\Jobs\SendPrayerChainSmsJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\PrayerUpdate;
use App\Notifications\PrayerRequestAnsweredNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PrayerRequestShow extends Component
{
    public Branch $branch;

    public PrayerRequest $prayerRequest;

    public bool $editing = false;

    // Form fields
    public string $title = '';

    public string $description = '';

    public string $category = '';

    public string $privacy = '';

    public ?string $member_id = null;

    public ?string $cluster_id = null;

    // Delete modal
    public bool $showDeleteModal = false;

    // Mark as answered modal
    public bool $showAnsweredModal = false;

    public string $answer_details = '';

    // Add update modal
    public bool $showAddUpdateModal = false;

    public string $update_content = '';

    public function mount(Branch $branch, PrayerRequest $prayerRequest): void
    {
        $this->authorize('view', $prayerRequest);
        $this->branch = $branch;
        $this->prayerRequest = $prayerRequest;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->prayerRequest);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->prayerRequest);
    }

    #[Computed]
    public function canMarkAnswered(): bool
    {
        return auth()->user()->can('markAnswered', $this->prayerRequest)
            && $this->prayerRequest->isOpen();
    }

    #[Computed]
    public function canAddUpdate(): bool
    {
        return auth()->user()->can('addUpdate', $this->prayerRequest);
    }

    #[Computed]
    public function canSendPrayerChain(): bool
    {
        return auth()->user()->can('sendPrayerChain', $this->prayerRequest)
            && $this->prayerRequest->isOpen();
    }

    #[Computed]
    public function categories(): array
    {
        return PrayerRequestCategory::cases();
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
    public function updates(): Collection
    {
        return $this->prayerRequest->updates()->with('member')->get();
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
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'cluster_id' => ['nullable', 'uuid', 'exists:clusters,id'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->prayerRequest);

        $this->fill([
            'title' => $this->prayerRequest->title,
            'description' => $this->prayerRequest->description,
            'category' => $this->prayerRequest->category->value,
            'privacy' => $this->prayerRequest->privacy->value,
            'member_id' => $this->prayerRequest->member_id,
            'cluster_id' => $this->prayerRequest->cluster_id,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->prayerRequest);
        $validated = $this->validate();

        if ($validated['cluster_id'] === '') {
            $validated['cluster_id'] = null;
        }

        $this->prayerRequest->update($validated);
        $this->prayerRequest->refresh();

        $this->editing = false;
        $this->dispatch('prayer-request-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->prayerRequest);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->prayerRequest);
        $this->prayerRequest->delete();
        $this->dispatch('prayer-request-deleted');
        $this->redirect(route('prayer-requests.index', $this->branch), navigate: true);
    }

    public function openAnsweredModal(): void
    {
        $this->authorize('markAnswered', $this->prayerRequest);
        $this->answer_details = '';
        $this->showAnsweredModal = true;
    }

    public function cancelAnswered(): void
    {
        $this->showAnsweredModal = false;
        $this->answer_details = '';
    }

    public function markAsAnswered(): void
    {
        $this->authorize('markAnswered', $this->prayerRequest);

        $this->validate([
            'answer_details' => ['nullable', 'string'],
        ]);

        $this->prayerRequest->markAsAnswered($this->answer_details ?: null);
        $this->prayerRequest->refresh();

        // Notify the submitter if they have an email
        $member = $this->prayerRequest->member;
        if ($member && $member->email) {
            $user = \App\Models\User::where('email', $member->email)->first();
            if ($user) {
                $user->notify(new PrayerRequestAnsweredNotification($this->prayerRequest));
            }
        }

        $this->showAnsweredModal = false;
        $this->answer_details = '';
        $this->dispatch('prayer-request-answered');
    }

    public function openAddUpdateModal(): void
    {
        $this->authorize('addUpdate', $this->prayerRequest);
        $this->update_content = '';
        $this->showAddUpdateModal = true;
    }

    public function cancelAddUpdate(): void
    {
        $this->showAddUpdateModal = false;
        $this->update_content = '';
    }

    public function addUpdate(): void
    {
        $this->authorize('addUpdate', $this->prayerRequest);

        $this->validate([
            'update_content' => ['required', 'string'],
        ]);

        // Find a member for the current user (by email match)
        $user = auth()->user();
        $member = Member::where('email', $user->email)
            ->where('primary_branch_id', $this->branch->id)
            ->first();

        PrayerUpdate::create([
            'prayer_request_id' => $this->prayerRequest->id,
            'member_id' => $member?->id ?? $this->prayerRequest->member_id,
            'content' => $this->update_content,
        ]);

        unset($this->updates);

        $this->showAddUpdateModal = false;
        $this->update_content = '';
        $this->dispatch('prayer-update-added');
    }

    public function sendPrayerChain(): void
    {
        $this->authorize('sendPrayerChain', $this->prayerRequest);

        SendPrayerChainSmsJob::dispatch($this->prayerRequest, auth()->user());

        $this->dispatch('prayer-chain-sent');
    }

    public function render()
    {
        return view('livewire.prayer-requests.prayer-request-show');
    }
}
