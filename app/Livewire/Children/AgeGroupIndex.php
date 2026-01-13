<?php

declare(strict_types=1);

namespace App\Livewire\Children;

use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AgeGroupIndex extends Component
{
    public Branch $branch;

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form fields
    public string $name = '';

    public string $description = '';

    public int $minAge = 0;

    public int $maxAge = 2;

    public string $color = 'blue';

    public bool $isActive = true;

    public int $sortOrder = 0;

    public ?AgeGroup $editingAgeGroup = null;

    public ?AgeGroup $deletingAgeGroup = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [AgeGroup::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return AgeGroup::query()
            ->where('branch_id', $this->branch->id)
            ->ordered()
            ->withCount('children')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [AgeGroup::class, $this->branch]);
    }

    #[Computed]
    public function unassignedChildrenCount(): int
    {
        return Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->children()
            ->whereNull('age_group_id')
            ->count();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'minAge' => ['required', 'integer', 'min:0', 'max:17'],
            'maxAge' => ['required', 'integer', 'min:0', 'max:17', 'gte:minAge'],
            'color' => ['nullable', 'string', 'max:20'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [AgeGroup::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [AgeGroup::class, $this->branch]);
        $this->validate();

        AgeGroup::create([
            'branch_id' => $this->branch->id,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'min_age' => $this->minAge,
            'max_age' => $this->maxAge,
            'color' => $this->color,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->ageGroups);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('age-group-created');
    }

    public function edit(AgeGroup $ageGroup): void
    {
        $this->authorize('update', $ageGroup);
        $this->editingAgeGroup = $ageGroup;
        $this->fill([
            'name' => $ageGroup->name,
            'description' => $ageGroup->description ?? '',
            'minAge' => $ageGroup->min_age,
            'maxAge' => $ageGroup->max_age,
            'color' => $ageGroup->color ?? 'blue',
            'isActive' => $ageGroup->is_active,
            'sortOrder' => $ageGroup->sort_order,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingAgeGroup);
        $this->validate();

        $this->editingAgeGroup->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'min_age' => $this->minAge,
            'max_age' => $this->maxAge,
            'color' => $this->color,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->ageGroups);

        $this->showEditModal = false;
        $this->editingAgeGroup = null;
        $this->resetForm();
        $this->dispatch('age-group-updated');
    }

    public function confirmDelete(AgeGroup $ageGroup): void
    {
        $this->authorize('delete', $ageGroup);
        $this->deletingAgeGroup = $ageGroup;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingAgeGroup);
        $this->deletingAgeGroup->delete();

        unset($this->ageGroups);

        $this->showDeleteModal = false;
        $this->deletingAgeGroup = null;
        $this->dispatch('age-group-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingAgeGroup = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingAgeGroup = null;
    }

    public function autoAssignAll(): void
    {
        $this->authorize('create', [AgeGroup::class, $this->branch]);

        Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->children()
            ->whereNull('age_group_id')
            ->each(fn (Member $child) => $child->assignAgeGroupByAge());

        unset($this->ageGroups);
        unset($this->unassignedChildrenCount);

        $this->dispatch('children-auto-assigned');
    }

    protected function resetForm(): void
    {
        $this->reset(['name', 'description', 'isActive', 'sortOrder']);
        $this->minAge = 0;
        $this->maxAge = 2;
        $this->color = 'blue';
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.children.age-group-index');
    }
}
