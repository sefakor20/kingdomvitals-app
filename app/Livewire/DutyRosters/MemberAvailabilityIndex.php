<?php

namespace App\Livewire\DutyRosters;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberUnavailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MemberAvailabilityIndex extends Component
{
    public Branch $branch;

    public ?string $memberFilter = null;

    public ?string $monthFilter = null;

    // Form properties
    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

    public ?string $member_id = null;

    public string $unavailable_date = '';

    public string $end_date = '';

    public string $reason = '';

    public bool $isDateRange = false;

    public ?MemberUnavailability $deletingUnavailability = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [MemberUnavailability::class, $branch]);
        $this->branch = $branch;
        $this->monthFilter = now()->format('Y-m');
    }

    #[Computed]
    public function unavailabilities(): Collection
    {
        $query = MemberUnavailability::where('branch_id', $this->branch->id)
            ->with('member');

        if ($this->memberFilter) {
            $query->where('member_id', $this->memberFilter);
        }

        if ($this->monthFilter) {
            $date = Carbon::parse($this->monthFilter.'-01');
            $query->whereYear('unavailable_date', $date->year)
                ->whereMonth('unavailable_date', $date->month);
        }

        return $query->orderBy('unavailable_date')->get();
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
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [MemberUnavailability::class, $this->branch]);
    }

    protected function rules(): array
    {
        $rules = [
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'unavailable_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->isDateRange) {
            $rules['end_date'] = ['required', 'date', 'after_or_equal:unavailable_date'];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->authorize('create', [MemberUnavailability::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [MemberUnavailability::class, $this->branch]);
        $validated = $this->validate();

        $dates = collect();

        if ($this->isDateRange && ! empty($validated['end_date'])) {
            $start = Carbon::parse($validated['unavailable_date']);
            $end = Carbon::parse($validated['end_date']);

            while ($start->lte($end)) {
                $dates->push($start->copy());
                $start->addDay();
            }
        } else {
            $dates->push(Carbon::parse($validated['unavailable_date']));
        }

        $reason = $validated['reason'] === '' ? null : $validated['reason'];
        $created = 0;

        foreach ($dates as $date) {
            // Check if unavailability already exists for this member and date
            $exists = MemberUnavailability::where('member_id', $validated['member_id'])
                ->where('branch_id', $this->branch->id)
                ->whereDate('unavailable_date', $date)
                ->exists();

            if (! $exists) {
                MemberUnavailability::create([
                    'member_id' => $validated['member_id'],
                    'branch_id' => $this->branch->id,
                    'unavailable_date' => $date,
                    'reason' => $reason,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            }
        }

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('unavailability-created', count: $created);
    }

    public function confirmDelete(MemberUnavailability $unavailability): void
    {
        $this->authorize('delete', $unavailability);
        $this->deletingUnavailability = $unavailability;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingUnavailability);
        $this->deletingUnavailability->delete();
        $this->showDeleteModal = false;
        $this->deletingUnavailability = null;
        $this->dispatch('unavailability-deleted');
    }

    public function previousMonth(): void
    {
        $date = Carbon::parse($this->monthFilter.'-01')->subMonth();
        $this->monthFilter = $date->format('Y-m');
    }

    public function nextMonth(): void
    {
        $date = Carbon::parse($this->monthFilter.'-01')->addMonth();
        $this->monthFilter = $date->format('Y-m');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUnavailability = null;
    }

    private function resetForm(): void
    {
        $this->reset(['member_id', 'unavailable_date', 'end_date', 'reason']);
        $this->isDateRange = false;
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.duty-rosters.member-availability-index');
    }
}
