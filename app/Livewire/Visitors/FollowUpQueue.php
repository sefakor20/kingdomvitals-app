<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpOutcome;
use App\Enums\FollowUpType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FollowUpQueue extends Component
{
    use AuthorizesRequests;
    use HasFilterableQuery;

    public Branch $branch;

    // Filters
    public string $search = '';

    public ?string $typeFilter = null;

    public ?string $memberFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // Complete follow-up modal
    public bool $showCompleteModal = false;

    public ?VisitorFollowUp $completingFollowUp = null;

    public string $completionOutcome = 'successful';

    public string $completionNotes = '';

    public ?string $completionPerformedBy = null;

    // Reschedule modal
    public bool $showRescheduleModal = false;

    public ?VisitorFollowUp $reschedulingFollowUp = null;

    public ?string $rescheduleDate = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [VisitorFollowUp::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function overdueFollowUps(): Collection
    {
        return $this->getBaseQuery()
            ->where('scheduled_at', '<', today())
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    #[Computed]
    public function dueTodayFollowUps(): Collection
    {
        return $this->getBaseQuery()
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    #[Computed]
    public function upcomingFollowUps(): Collection
    {
        return $this->getBaseQuery()
            ->whereDate('scheduled_at', '>', today())
            ->whereDate('scheduled_at', '<=', today()->addDays(7))
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = VisitorFollowUp::query()
            ->whereHas('visitor', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->where('outcome', FollowUpOutcome::Pending);

        return [
            'total' => (clone $baseQuery)->count(),
            'overdue' => (clone $baseQuery)->where('scheduled_at', '<', today())->count(),
            'dueToday' => (clone $baseQuery)->whereDate('scheduled_at', today())->count(),
            'upcoming' => (clone $baseQuery)
                ->whereDate('scheduled_at', '>', today())
                ->whereDate('scheduled_at', '<=', today()->addDays(7))
                ->count(),
        ];
    }

    #[Computed]
    public function followUpTypes(): array
    {
        return FollowUpType::cases();
    }

    #[Computed]
    public function followUpOutcomes(): array
    {
        return collect(FollowUpOutcome::cases())
            ->filter(fn ($outcome) => $outcome !== FollowUpOutcome::Pending)
            ->all();
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
    public function hasActiveFilters(): bool
    {
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->typeFilter)
            || $this->isFilterActive($this->memberFilter)
            || $this->isFilterActive($this->dateFrom)
            || $this->isFilterActive($this->dateTo);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'memberFilter', 'dateFrom', 'dateTo']);
        $this->clearComputedCache();
    }

    // Complete follow-up modal methods
    public function openCompleteModal(VisitorFollowUp $followUp): void
    {
        $this->authorize('update', $followUp);
        $this->completingFollowUp = $followUp;
        $this->completionOutcome = 'successful';
        $this->completionNotes = $followUp->notes ?? '';
        $this->completionPerformedBy = $followUp->performed_by;
        $this->showCompleteModal = true;
    }

    public function completeFollowUp(): void
    {
        if (! $this->completingFollowUp) {
            return;
        }

        $this->authorize('update', $this->completingFollowUp);

        $validated = $this->validate([
            'completionOutcome' => ['required', 'string', 'in:successful,no_answer,voicemail,callback,not_interested,wrong_number,rescheduled'],
            'completionNotes' => ['nullable', 'string'],
            'completionPerformedBy' => ['nullable', 'uuid', 'exists:members,id'],
        ]);

        $this->completingFollowUp->update([
            'outcome' => $validated['completionOutcome'],
            'notes' => $validated['completionNotes'] ?: null,
            'performed_by' => $validated['completionPerformedBy'] ?: null,
            'completed_at' => now(),
        ]);

        $visitor = $this->completingFollowUp->visitor;

        // Update visitor stats
        $visitor->update([
            'last_follow_up_at' => now(),
            'follow_up_count' => $visitor->follow_up_count + 1,
        ]);

        // Update next follow-up date
        $nextPending = $visitor->followUps()
            ->where('outcome', FollowUpOutcome::Pending->value)
            ->where('id', '!=', $this->completingFollowUp->id)
            ->orderBy('scheduled_at')
            ->first();

        $visitor->update(['next_follow_up_at' => $nextPending?->scheduled_at]);

        // If first follow-up and status is 'new', update to 'followed_up'
        if ($visitor->status->value === 'new') {
            $visitor->update(['status' => 'followed_up']);
        }

        $this->closeCompleteModal();
        $this->clearComputedCache();
        $this->dispatch('follow-up-completed');
    }

    public function closeCompleteModal(): void
    {
        $this->showCompleteModal = false;
        $this->completingFollowUp = null;
        $this->completionOutcome = 'successful';
        $this->completionNotes = '';
        $this->completionPerformedBy = null;
        $this->resetValidation();
    }

    // Reschedule modal methods
    public function openRescheduleModal(VisitorFollowUp $followUp): void
    {
        $this->authorize('update', $followUp);
        $this->reschedulingFollowUp = $followUp;
        $this->rescheduleDate = now()->addDay()->format('Y-m-d\TH:i');
        $this->showRescheduleModal = true;
    }

    public function rescheduleFollowUp(): void
    {
        if (! $this->reschedulingFollowUp) {
            return;
        }

        $this->authorize('update', $this->reschedulingFollowUp);

        $validated = $this->validate([
            'rescheduleDate' => ['required', 'date', 'after:now'],
        ]);

        $this->reschedulingFollowUp->update([
            'scheduled_at' => $validated['rescheduleDate'],
        ]);

        $visitor = $this->reschedulingFollowUp->visitor;

        // Update visitor's next_follow_up_at if this is earlier
        $earliestPending = $visitor->followUps()
            ->where('outcome', FollowUpOutcome::Pending->value)
            ->orderBy('scheduled_at')
            ->first();

        $visitor->update(['next_follow_up_at' => $earliestPending?->scheduled_at]);

        $this->closeRescheduleModal();
        $this->clearComputedCache();
        $this->dispatch('follow-up-rescheduled');
    }

    public function closeRescheduleModal(): void
    {
        $this->showRescheduleModal = false;
        $this->reschedulingFollowUp = null;
        $this->rescheduleDate = null;
        $this->resetValidation();
    }

    private function getBaseQuery()
    {
        $query = VisitorFollowUp::query()
            ->whereHas('visitor', function ($q) {
                $q->where('branch_id', $this->branch->id);

                // Apply search filter on visitor
                if ($this->search) {
                    $q->where(function ($sq) {
                        $sq->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%");
                    });
                }
            })
            ->where('outcome', FollowUpOutcome::Pending)
            ->with(['visitor', 'performedBy']);

        // Apply type filter
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        // Apply member filter
        if ($this->memberFilter) {
            if ($this->memberFilter === 'unassigned') {
                $query->whereNull('performed_by');
            } else {
                $query->where('performed_by', $this->memberFilter);
            }
        }

        // Apply date range filters
        if ($this->dateFrom) {
            $query->where('scheduled_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('scheduled_at', '<=', $this->dateTo);
        }

        return $query;
    }

    private function clearComputedCache(): void
    {
        unset($this->overdueFollowUps);
        unset($this->dueTodayFollowUps);
        unset($this->upcomingFollowUps);
        unset($this->stats);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.visitors.follow-up-queue');
    }
}
