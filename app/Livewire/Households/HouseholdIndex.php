<?php

declare(strict_types=1);

namespace App\Livewire\Households;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HouseholdIndex extends Component
{
    public Branch $branch;

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public string $address = '';

    public ?string $head_id = null;

    public ?Household $editingHousehold = null;

    public ?Household $deletingHousehold = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Household::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function households(): Collection
    {
        $query = Household::where('branch_id', $this->branch->id)
            ->withCount('members');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('head', function ($hq) use ($search) {
                        $hq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->with(['head'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableHeads(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->adults()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Household::class, $this->branch]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'head_id' => ['nullable', 'uuid', 'exists:members,id'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Household::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Household::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

        if (empty($validated['head_id'])) {
            $validated['head_id'] = null;
        }
        if (empty($validated['address'])) {
            $validated['address'] = null;
        }

        $household = Household::create($validated);

        // If a head is selected, update their household assignment
        if ($household->head_id) {
            Member::where('id', $household->head_id)->update([
                'household_id' => $household->id,
                'household_role' => 'head',
            ]);
        }

        $this->showCreateModal = false;
        $this->resetForm();
        unset($this->households);
    }

    public function edit(Household $household): void
    {
        $this->authorize('update', $household);
        $this->editingHousehold = $household;
        $this->fill([
            'name' => $household->name,
            'address' => $household->address ?? '',
            'head_id' => $household->head_id,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingHousehold);
        $validated = $this->validate();

        if (empty($validated['head_id'])) {
            $validated['head_id'] = null;
        }
        if (empty($validated['address'])) {
            $validated['address'] = null;
        }

        $oldHeadId = $this->editingHousehold->head_id;
        $this->editingHousehold->update($validated);

        // Update head member's household assignment if changed
        if ($validated['head_id'] && $validated['head_id'] !== $oldHeadId) {
            // Remove old head's role
            if ($oldHeadId) {
                Member::where('id', $oldHeadId)->update([
                    'household_role' => null,
                ]);
            }
            // Set new head
            Member::where('id', $validated['head_id'])->update([
                'household_id' => $this->editingHousehold->id,
                'household_role' => 'head',
            ]);
        }

        $this->showEditModal = false;
        $this->editingHousehold = null;
        $this->resetForm();
        unset($this->households);
    }

    public function confirmDelete(Household $household): void
    {
        $this->authorize('delete', $household);
        $this->deletingHousehold = $household;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingHousehold);

        // Remove household assignment from all members
        Member::where('household_id', $this->deletingHousehold->id)->update([
            'household_id' => null,
            'household_role' => null,
        ]);

        $this->deletingHousehold->delete();
        $this->showDeleteModal = false;
        $this->deletingHousehold = null;
        unset($this->households);
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingHousehold = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingHousehold = null;
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'address', 'head_id']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.households.household-index');
    }
}
