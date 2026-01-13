<?php

declare(strict_types=1);

namespace App\Livewire\Children;

use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ChildrenDashboard extends Component
{
    public Branch $branch;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Member::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function stats(): array
    {
        $childrenQuery = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->children();

        return [
            'totalChildren' => (clone $childrenQuery)->count(),
            'unassignedChildren' => (clone $childrenQuery)->whereNull('age_group_id')->count(),
            'withEmergencyContact' => (clone $childrenQuery)->whereHas('emergencyContacts')->count(),
            'withMedicalInfo' => (clone $childrenQuery)->whereHas('medicalInfo')->count(),
            'checkedInToday' => $this->getCheckedInToday(),
        ];
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return AgeGroup::query()
            ->where('branch_id', $this->branch->id)
            ->active()
            ->ordered()
            ->withCount('children')
            ->get();
    }

    #[Computed]
    public function recentCheckIns(): Collection
    {
        return ChildrenCheckinSecurity::query()
            ->whereHas('attendance', fn ($q) => $q->whereHas('service', fn ($sq) => $sq->where('branch_id', $this->branch->id)))
            ->with(['child', 'guardian', 'attendance.service'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function childrenByAgeGroup(): array
    {
        $ageGroups = AgeGroup::query()
            ->where('branch_id', $this->branch->id)
            ->active()
            ->ordered()
            ->withCount('children')
            ->get();

        $unassignedCount = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->children()
            ->whereNull('age_group_id')
            ->count();

        $data = $ageGroups->map(fn ($ag): array => [
            'name' => $ag->name,
            'count' => $ag->children_count,
            'color' => $ag->color ?? '#6b7280',
        ])->toArray();

        if ($unassignedCount > 0) {
            $data[] = [
                'name' => 'Unassigned',
                'count' => $unassignedCount,
                'color' => '#f59e0b',
            ];
        }

        return $data;
    }

    protected function getCheckedInToday(): int
    {
        return ChildrenCheckinSecurity::query()
            ->whereHas('attendance', fn ($q) => $q->where('date', now()->format('Y-m-d'))
                ->whereHas('service', fn ($sq) => $sq->where('branch_id', $this->branch->id)))
            ->where('is_checked_out', false)
            ->count();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.children.children-dashboard');
    }
}
