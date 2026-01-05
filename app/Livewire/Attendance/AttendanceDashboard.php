<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Service;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AttendanceDashboard extends Component
{
    public Branch $branch;

    public Service $service;

    public ?string $selectedDate = null;

    public function mount(Branch $branch, Service $service): void
    {
        $this->authorize('viewAny', [Attendance::class, $branch]);
        $this->branch = $branch;
        $this->service = $service;
        $this->selectedDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function stats(): array
    {
        $todayCount = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->count();

        $membersCount = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->whereNotNull('member_id')
            ->count();

        $visitorsCount = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->whereNotNull('visitor_id')
            ->count();

        // Get last week's count for comparison
        $lastWeekDate = now()->parse($this->selectedDate)->subWeek()->format('Y-m-d');
        $lastWeekCount = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $lastWeekDate)
            ->count();

        // Calculate percentage change
        $percentChange = $lastWeekCount > 0
            ? round((($todayCount - $lastWeekCount) / $lastWeekCount) * 100, 1)
            : ($todayCount > 0 ? 100 : 0);

        // Capacity percentage
        $capacity = $this->service->capacity;
        $capacityPercent = $capacity > 0
            ? min(round(($todayCount / $capacity) * 100, 1), 100)
            : 0;

        return [
            'total' => $todayCount,
            'members' => $membersCount,
            'visitors' => $visitorsCount,
            'lastWeek' => $lastWeekCount,
            'percentChange' => $percentChange,
            'capacity' => $capacity,
            'capacityPercent' => $capacityPercent,
        ];
    }

    #[Computed]
    public function recentCheckIns(): Collection
    {
        return Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->with(['member', 'visitor'])
            ->orderBy('check_in_time', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->member?->fullName() ?? $a->visitor?->fullName() ?? 'Unknown',
                'type' => $a->member_id ? 'member' : 'visitor',
                'photo_url' => $a->member?->photo_url,
                'time' => $a->check_in_time ? substr($a->check_in_time, 0, 5) : '-',
                'method' => $a->check_in_method?->value ?? 'manual',
            ]);
    }

    #[Computed]
    public function checkInsByHour(): array
    {
        $attendance = Attendance::query()
            ->where('service_id', $this->service->id)
            ->where('date', $this->selectedDate)
            ->whereNotNull('check_in_time')
            ->get();

        $hourly = [];
        for ($h = 5; $h <= 22; $h++) {
            $hourly[$h] = 0;
        }

        foreach ($attendance as $a) {
            $hour = (int) substr($a->check_in_time, 0, 2);
            if (isset($hourly[$hour])) {
                $hourly[$hour]++;
            }
        }

        return $hourly;
    }

    public function refreshStats(): void
    {
        unset($this->stats);
        unset($this->recentCheckIns);
        unset($this->checkInsByHour);
    }

    public function render()
    {
        return view('livewire.attendance.attendance-dashboard');
    }
}
