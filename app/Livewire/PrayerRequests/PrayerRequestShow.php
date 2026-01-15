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
use App\Services\PlanAccessService;
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

    public bool $is_anonymous = false;

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

    /**
     * Get the count of members who would receive a prayer chain SMS.
     */
    #[Computed]
    public function prayerChainRecipientCount(): int
    {
        $query = Member::query()
            ->notOptedOutOfSms()
            ->whereNotNull('phone');

        // If prayer request is assigned to a cluster, only notify that cluster's members
        if ($this->prayerRequest->cluster_id) {
            $query->whereHas('clusters', function ($q): void {
                $q->where('clusters.id', $this->prayerRequest->cluster_id);
            });
        } else {
            // Otherwise, notify all branch members
            $query->where('primary_branch_id', $this->prayerRequest->branch_id);
        }

        // Exclude the member who submitted the request
        if ($this->prayerRequest->member_id) {
            $query->where('id', '!=', $this->prayerRequest->member_id);
        }

        return $query->count();
    }

    /**
     * Get SMS quota information for display.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function smsQuota(): array
    {
        return app(PlanAccessService::class)->getSmsQuota();
    }

    /**
     * Check if sending the prayer chain would exceed SMS quota.
     */
    #[Computed]
    public function canSendPrayerChainWithinQuota(): bool
    {
        return app(PlanAccessService::class)->canSendSms($this->prayerChainRecipientCount);
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
            'is_anonymous' => ['boolean'],
            'member_id' => ['nullable', 'required_if:is_anonymous,false', 'uuid', 'exists:members,id'],
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
            'is_anonymous' => $this->prayerRequest->isAnonymous(),
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

        // Handle anonymous submissions
        if ($this->is_anonymous) {
            $validated['member_id'] = null;
        }

        // Remove is_anonymous from validated data as it's not a model field
        unset($validated['is_anonymous']);

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

        // Check SMS quota before sending
        $recipientCount = $this->prayerChainRecipientCount;
        if (! app(PlanAccessService::class)->canSendSms($recipientCount)) {
            $quota = $this->smsQuota;
            $this->dispatch('toast',
                type: 'error',
                message: __('Insufficient SMS credits. You need :count but have :remaining remaining this month.', [
                    'count' => $recipientCount,
                    'remaining' => $quota['remaining'] ?? 0,
                ])
            );

            return;
        }

        SendPrayerChainSmsJob::dispatch($this->prayerRequest, auth()->user());

        // Invalidate SMS count cache for quota tracking
        app(PlanAccessService::class)->invalidateCountCache('sms');

        $this->dispatch('prayer-chain-sent');
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.prayer-requests.prayer-request-show');
    }
}
