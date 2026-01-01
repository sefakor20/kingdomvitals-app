<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Enums\VisitorStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class VisitorShow extends Component
{
    public Branch $branch;

    public Visitor $visitor;

    public bool $editing = false;

    // Form fields
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $visit_date = null;

    public string $status = '';

    public string $how_did_you_hear = '';

    public string $notes = '';

    public ?string $assigned_to = null;

    // Modals
    public bool $showDeleteModal = false;

    public bool $showConvertModal = false;

    public ?string $convertToMemberId = null;

    // Follow-up modals
    public bool $showAddFollowUpModal = false;

    public bool $showScheduleFollowUpModal = false;

    public ?VisitorFollowUp $completingFollowUp = null;

    // Follow-up form fields
    public string $followUpType = 'call';

    public string $followUpOutcome = 'successful';

    public string $followUpNotes = '';

    public ?string $followUpPerformedBy = null;

    public ?string $followUpScheduledAt = null;

    public function mount(Branch $branch, Visitor $visitor): void
    {
        $this->authorize('view', $visitor);
        $this->branch = $branch;
        $this->visitor = $visitor;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->visitor);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->visitor);
    }

    #[Computed]
    public function statuses(): array
    {
        return VisitorStatus::cases();
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
    public function howDidYouHearOptions(): array
    {
        return [
            'Friend or family',
            'Social media',
            'Church website',
            'Google search',
            'Passed by the church',
            'Flyer or brochure',
            'Community event',
            'Other',
        ];
    }

    #[Computed]
    public function attendanceCount(): int
    {
        return $this->visitor->attendance()->count();
    }

    #[Computed]
    public function followUps(): Collection
    {
        return $this->visitor->followUps()
            ->with('performedBy')
            ->get();
    }

    #[Computed]
    public function pendingFollowUps(): Collection
    {
        return $this->visitor->followUps()
            ->where('outcome', FollowUpOutcome::Pending->value)
            ->with('performedBy')
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    #[Computed]
    public function followUpTypes(): array
    {
        return FollowUpType::cases();
    }

    #[Computed]
    public function followUpOutcomes(): array
    {
        return FollowUpOutcome::cases();
    }

    #[Computed]
    public function canAddFollowUp(): bool
    {
        return auth()->user()->can('create', [VisitorFollowUp::class, $this->branch]);
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'visit_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:new,followed_up,returning,converted,not_interested'],
            'how_did_you_hear' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'uuid', 'exists:members,id'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->visitor);

        $this->fill([
            'first_name' => $this->visitor->first_name,
            'last_name' => $this->visitor->last_name,
            'email' => $this->visitor->email ?? '',
            'phone' => $this->visitor->phone ?? '',
            'visit_date' => $this->visitor->visit_date?->format('Y-m-d'),
            'status' => $this->visitor->status->value,
            'how_did_you_hear' => $this->visitor->how_did_you_hear ?? '',
            'notes' => $this->visitor->notes ?? '',
            'assigned_to' => $this->visitor->assigned_to,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->visitor);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['email', 'phone', 'how_did_you_hear', 'notes', 'assigned_to'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->visitor->update($validated);
        $this->visitor->refresh();

        $this->editing = false;
        $this->dispatch('visitor-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->visitor);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->visitor);
        $this->visitor->delete();
        $this->dispatch('visitor-deleted');
        $this->redirect(route('visitors.index', $this->branch), navigate: true);
    }

    public function openConvertModal(): void
    {
        $this->authorize('update', $this->visitor);
        $this->convertToMemberId = null;
        $this->showConvertModal = true;
    }

    public function cancelConvert(): void
    {
        $this->showConvertModal = false;
        $this->convertToMemberId = null;
    }

    public function convert(): void
    {
        $this->authorize('update', $this->visitor);

        $this->validate([
            'convertToMemberId' => ['required', 'uuid', 'exists:members,id'],
        ]);

        $this->visitor->update([
            'status' => VisitorStatus::Converted->value,
            'is_converted' => true,
            'converted_member_id' => $this->convertToMemberId,
        ]);

        $this->visitor->refresh();
        $this->showConvertModal = false;
        $this->convertToMemberId = null;
        $this->dispatch('visitor-converted');
    }

    // Follow-up methods
    public function openAddFollowUpModal(): void
    {
        $this->authorize('create', [VisitorFollowUp::class, $this->branch]);
        $this->resetFollowUpForm();
        $this->showAddFollowUpModal = true;
    }

    public function openScheduleFollowUpModal(): void
    {
        $this->authorize('create', [VisitorFollowUp::class, $this->branch]);
        $this->resetFollowUpForm();
        $this->followUpScheduledAt = now()->addDay()->format('Y-m-d\TH:i');
        $this->showScheduleFollowUpModal = true;
    }

    public function cancelFollowUpModal(): void
    {
        $this->showAddFollowUpModal = false;
        $this->showScheduleFollowUpModal = false;
        $this->completingFollowUp = null;
        $this->resetFollowUpForm();
    }

    public function addFollowUp(): void
    {
        $this->authorize('create', [VisitorFollowUp::class, $this->branch]);

        $validated = $this->validate([
            'followUpType' => ['required', 'string', 'in:call,sms,email,visit,whatsapp,other'],
            'followUpOutcome' => ['required', 'string', 'in:successful,no_answer,voicemail,callback,not_interested,wrong_number,rescheduled,pending'],
            'followUpNotes' => ['nullable', 'string'],
            'followUpPerformedBy' => ['nullable', 'uuid', 'exists:members,id'],
        ]);

        VisitorFollowUp::create([
            'visitor_id' => $this->visitor->id,
            'type' => $validated['followUpType'],
            'outcome' => $validated['followUpOutcome'],
            'notes' => $validated['followUpNotes'] ?: null,
            'performed_by' => $validated['followUpPerformedBy'] ?: null,
            'created_by_user_id' => auth()->id(),
            'completed_at' => now(),
            'is_scheduled' => false,
        ]);

        // Update visitor stats
        $this->visitor->update([
            'last_follow_up_at' => now(),
            'follow_up_count' => $this->visitor->follow_up_count + 1,
        ]);

        // If first follow-up and status is 'new', update to 'followed_up'
        if ($this->visitor->status === VisitorStatus::New) {
            $this->visitor->update(['status' => VisitorStatus::FollowedUp->value]);
        }

        $this->visitor->refresh();
        unset($this->followUps);
        unset($this->pendingFollowUps);

        $this->showAddFollowUpModal = false;
        $this->resetFollowUpForm();
        $this->dispatch('follow-up-added');
    }

    public function scheduleFollowUp(): void
    {
        $this->authorize('create', [VisitorFollowUp::class, $this->branch]);

        $validated = $this->validate([
            'followUpType' => ['required', 'string', 'in:call,sms,email,visit,whatsapp,other'],
            'followUpNotes' => ['nullable', 'string'],
            'followUpPerformedBy' => ['nullable', 'uuid', 'exists:members,id'],
            'followUpScheduledAt' => ['required', 'date', 'after:now'],
        ]);

        VisitorFollowUp::create([
            'visitor_id' => $this->visitor->id,
            'type' => $validated['followUpType'],
            'outcome' => FollowUpOutcome::Pending->value,
            'notes' => $validated['followUpNotes'] ?: null,
            'performed_by' => $validated['followUpPerformedBy'] ?: null,
            'created_by_user_id' => auth()->id(),
            'scheduled_at' => $validated['followUpScheduledAt'],
            'is_scheduled' => true,
        ]);

        // Update next follow-up date if this is earlier
        if (! $this->visitor->next_follow_up_at || $this->visitor->next_follow_up_at > $validated['followUpScheduledAt']) {
            $this->visitor->update(['next_follow_up_at' => $validated['followUpScheduledAt']]);
        }

        $this->visitor->refresh();
        unset($this->followUps);
        unset($this->pendingFollowUps);

        $this->showScheduleFollowUpModal = false;
        $this->resetFollowUpForm();
        $this->dispatch('follow-up-scheduled');
    }

    public function startCompleteFollowUp(VisitorFollowUp $followUp): void
    {
        $this->authorize('update', $followUp);
        $this->completingFollowUp = $followUp;
        $this->followUpType = $followUp->type->value;
        $this->followUpNotes = $followUp->notes ?? '';
        $this->followUpPerformedBy = $followUp->performed_by;
        $this->followUpOutcome = 'successful';
    }

    public function completeFollowUp(): void
    {
        if (! $this->completingFollowUp) {
            return;
        }

        $this->authorize('update', $this->completingFollowUp);

        $validated = $this->validate([
            'followUpOutcome' => ['required', 'string', 'in:successful,no_answer,voicemail,callback,not_interested,wrong_number,rescheduled'],
            'followUpNotes' => ['nullable', 'string'],
            'followUpPerformedBy' => ['nullable', 'uuid', 'exists:members,id'],
        ]);

        $this->completingFollowUp->update([
            'outcome' => $validated['followUpOutcome'],
            'notes' => $validated['followUpNotes'] ?: null,
            'performed_by' => $validated['followUpPerformedBy'] ?: null,
            'completed_at' => now(),
        ]);

        // Update visitor stats
        $this->visitor->update([
            'last_follow_up_at' => now(),
            'follow_up_count' => $this->visitor->follow_up_count + 1,
        ]);

        // Update next follow-up date
        $nextPending = $this->visitor->followUps()
            ->where('outcome', FollowUpOutcome::Pending->value)
            ->where('id', '!=', $this->completingFollowUp->id)
            ->orderBy('scheduled_at')
            ->first();

        $this->visitor->update(['next_follow_up_at' => $nextPending?->scheduled_at]);

        // If first follow-up and status is 'new', update to 'followed_up'
        if ($this->visitor->status === VisitorStatus::New) {
            $this->visitor->update(['status' => VisitorStatus::FollowedUp->value]);
        }

        $this->visitor->refresh();
        unset($this->followUps);
        unset($this->pendingFollowUps);

        $this->completingFollowUp = null;
        $this->resetFollowUpForm();
        $this->dispatch('follow-up-completed');
    }

    public function cancelFollowUp(VisitorFollowUp $followUp): void
    {
        $this->authorize('delete', $followUp);

        $followUp->delete();

        // Update next follow-up date
        $nextPending = $this->visitor->followUps()
            ->where('outcome', FollowUpOutcome::Pending->value)
            ->orderBy('scheduled_at')
            ->first();

        $this->visitor->update(['next_follow_up_at' => $nextPending?->scheduled_at]);

        $this->visitor->refresh();
        unset($this->followUps);
        unset($this->pendingFollowUps);

        $this->dispatch('follow-up-cancelled');
    }

    private function resetFollowUpForm(): void
    {
        $this->followUpType = 'call';
        $this->followUpOutcome = 'successful';
        $this->followUpNotes = '';
        $this->followUpPerformedBy = null;
        $this->followUpScheduledAt = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.visitors.visitor-show');
    }
}
