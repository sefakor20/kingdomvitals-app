<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\CheckInMethod;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LiveCheckIn extends Component
{
    public Branch $branch;

    public Service $service;

    public string $searchQuery = '';

    public ?string $selectedDate = null;

    public ?string $lastCheckedInName = null;

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('create', [Attendance::class, $branch]);
        $this->branch = $branch;
        $this->service = $service;
        $this->selectedDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function searchResults(): Collection
    {
        if (strlen($this->searchQuery) < 2) {
            return collect();
        }

        $search = $this->searchQuery;

        // Get matching members
        $members = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->fullName(),
                'type' => 'member',
                'already_checked_in' => $this->isAlreadyCheckedIn('member', $m->id),
            ]);

        // Get matching visitors
        $visitors = Visitor::query()
            ->where('branch_id', $this->branch->id)
            ->whereNull('converted_member_id')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->fullName(),
                'type' => 'visitor',
                'already_checked_in' => $this->isAlreadyCheckedIn('visitor', $v->id),
            ]);

        return $members->concat($visitors)->sortBy('name')->take(12);
    }

    #[Computed]
    public function recentCheckIns(): Collection
    {
        return Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->with(['member', 'visitor'])
            ->orderBy('check_in_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'name' => $a->member?->fullName() ?? $a->visitor?->fullName() ?? 'Unknown',
                'type' => $a->member_id ? 'member' : 'visitor',
                'time' => $a->check_in_time ? substr($a->check_in_time, 0, 5) : '-',
            ]);
    }

    #[Computed]
    public function todayStats(): array
    {
        $attendance = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate);

        return [
            'total' => $attendance->count(),
            'members' => (clone $attendance)->whereNotNull('member_id')->count(),
            'visitors' => (clone $attendance)->whereNotNull('visitor_id')->count(),
        ];
    }

    public function checkIn(string $id, string $type): void
    {
        $this->authorize('create', [Attendance::class, $this->branch]);

        // Check if already checked in
        if ($this->isAlreadyCheckedIn($type, $id)) {
            $this->dispatch('already-checked-in');

            return;
        }

        $memberId = null;
        $visitorId = null;
        $name = '';

        if ($type === 'member') {
            $member = Member::where('id', $id)
                ->where('primary_branch_id', $this->branch->id)
                ->where('status', 'active')
                ->first();

            if (! $member) {
                $this->addError('checkIn', __('Invalid member selected.'));

                return;
            }

            $memberId = $member->id;
            $name = $member->fullName();
        } else {
            $visitor = Visitor::where('id', $id)
                ->where('branch_id', $this->branch->id)
                ->whereNull('converted_member_id')
                ->first();

            if (! $visitor) {
                $this->addError('checkIn', __('Invalid visitor selected.'));

                return;
            }

            $visitorId = $visitor->id;
            $name = $visitor->fullName();
        }

        Attendance::create([
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'member_id' => $memberId,
            'visitor_id' => $visitorId,
            'date' => $this->selectedDate,
            'check_in_time' => now()->format('H:i'),
            'check_in_method' => CheckInMethod::Kiosk,
        ]);

        // Clear search and show feedback
        $this->searchQuery = '';
        $this->lastCheckedInName = $name;

        // Reset computed properties
        unset($this->searchResults);
        unset($this->recentCheckIns);
        unset($this->todayStats);

        $this->dispatch('check-in-success', name: $name);
    }

    protected function isAlreadyCheckedIn(string $type, string $id): bool
    {
        $query = Attendance::where('service_id', $this->service->id)
            ->where('date', $this->selectedDate);

        if ($type === 'member') {
            return $query->where('member_id', $id)->exists();
        }

        return $query->where('visitor_id', $id)->exists();
    }

    public function render()
    {
        return view('livewire.attendance.live-check-in');
    }
}
