<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Enums\PredictionTier;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventAttendancePrediction;
use App\Services\AI\EventPredictionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class EventAttendancePredictions extends Component
{
    use WithPagination;

    public Branch $branch;

    public Event $event;

    #[Url]
    public string $tierFilter = '';

    #[Url]
    public string $invitedFilter = '';

    #[Url]
    public string $search = '';

    public function mount(Branch $branch, Event $event): void
    {
        $this->authorize('view', $branch);
        $this->branch = $branch;
        $this->event = $event;
    }

    /**
     * Reset all filters to defaults.
     */
    public function resetFilters(): void
    {
        $this->tierFilter = '';
        $this->invitedFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Regenerate predictions for this event.
     */
    public function regeneratePredictions(): void
    {
        $service = app(EventPredictionService::class);

        if (! $service->isEnabled()) {
            $this->dispatch('notify', type: 'error', message: __('Event prediction feature is disabled.'));

            return;
        }

        $predictions = $service->predictForEvent($this->event);
        $saved = $service->savePredictions($predictions, $this->event);

        $this->dispatch('notify', type: 'success', message: __('Generated :count predictions.', ['count' => $saved]));
    }

    /**
     * Mark a member as invited.
     */
    public function markAsInvited(string $predictionId): void
    {
        $prediction = EventAttendancePrediction::find($predictionId);

        if ($prediction) {
            $prediction->update([
                'invitation_sent' => true,
                'invitation_sent_at' => now(),
            ]);

            $this->dispatch('notify', type: 'success', message: __('Member marked as invited.'));
        }
    }

    /**
     * Send invitations to high probability members.
     */
    public function sendBulkInvitations(): void
    {
        $toInvite = EventAttendancePrediction::query()
            ->where('event_id', $this->event->id)
            ->whereIn('prediction_tier', [PredictionTier::High, PredictionTier::Medium])
            ->where('invitation_sent', false)
            ->limit(50)
            ->get();

        foreach ($toInvite as $prediction) {
            $prediction->update([
                'invitation_sent' => true,
                'invitation_sent_at' => now(),
            ]);
            // TODO: Dispatch actual invitation job
        }

        $this->dispatch('notify', type: 'success', message: __('Marked :count members as invited.', ['count' => $toInvite->count()]));
    }

    #[Computed]
    public function predictions(): LengthAwarePaginator
    {
        $query = EventAttendancePrediction::query()
            ->where('event_id', $this->event->id)
            ->with('member')
            ->orderByDesc('attendance_probability');

        if ($this->tierFilter !== '') {
            $tier = PredictionTier::tryFrom($this->tierFilter);
            if ($tier) {
                $query->where('prediction_tier', $tier);
            }
        }

        if ($this->invitedFilter !== '') {
            match ($this->invitedFilter) {
                'invited' => $query->where('invitation_sent', true),
                'not_invited' => $query->where('invitation_sent', false),
                default => null,
            };
        }

        if ($this->search !== '') {
            $query->whereHas('member', function ($q): void {
                $q->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%');
            });
        }

        return $query->paginate(20);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function summaryStats(): array
    {
        $predictions = EventAttendancePrediction::where('event_id', $this->event->id)->get();

        return [
            'total' => $predictions->count(),
            'high' => $predictions->where('prediction_tier', PredictionTier::High)->count(),
            'medium' => $predictions->where('prediction_tier', PredictionTier::Medium)->count(),
            'low' => $predictions->where('prediction_tier', PredictionTier::Low)->count(),
            'invited' => $predictions->where('invitation_sent', true)->count(),
            'avg_probability' => $predictions->count() > 0
                ? round($predictions->avg('attendance_probability'), 1)
                : 0,
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableTiers(): array
    {
        return collect(PredictionTier::cases())
            ->mapWithKeys(fn (PredictionTier $tier) => [$tier->value => $tier->label()])
            ->all();
    }

    /**
     * Check if feature is enabled.
     */
    #[Computed]
    public function featureEnabled(): bool
    {
        return app(EventPredictionService::class)->isEnabled();
    }

    public function render(): View
    {
        return view('livewire.events.event-attendance-predictions');
    }
}
