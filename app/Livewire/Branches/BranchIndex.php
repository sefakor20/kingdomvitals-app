<?php

namespace App\Livewire\Branches;

use App\Enums\BranchStatus;
use App\Models\Tenant\Branch;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.sidebar')]
class BranchIndex extends Component
{
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $name = '';

    public string $slug = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $country = '';

    public string $phone = '';

    public string $email = '';

    public ?int $capacity = null;

    public string $timezone = 'Africa/Accra';

    public string $status = 'active';

    public ?Branch $editingBranch = null;

    public ?Branch $deletingBranch = null;

    protected function rules(): array
    {
        $branchId = $this->editingBranch?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', "unique:branches,slug,{$branchId}"],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'timezone' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:active,inactive,pending,suspended'],
        ];
    }

    #[Computed]
    public function branches()
    {
        return Branch::query()
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return BranchStatus::cases();
    }

    public function updatedName(string $value): void
    {
        if (! $this->editingBranch) {
            $this->slug = Str::slug($value);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Branch::class);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', Branch::class);
        $validated = $this->validate();

        Branch::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('branch-created');
    }

    public function edit(Branch $branch): void
    {
        $this->authorize('update', $branch);
        $this->editingBranch = $branch;
        $this->fill([
            'name' => $branch->name,
            'slug' => $branch->slug,
            'address' => $branch->address ?? '',
            'city' => $branch->city ?? '',
            'state' => $branch->state ?? '',
            'zip' => $branch->zip ?? '',
            'country' => $branch->country ?? '',
            'phone' => $branch->phone ?? '',
            'email' => $branch->email ?? '',
            'capacity' => $branch->capacity,
            'timezone' => $branch->timezone,
            'status' => $branch->status->value,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingBranch);
        $validated = $this->validate();

        $this->editingBranch->update($validated);

        $this->showEditModal = false;
        $this->editingBranch = null;
        $this->resetForm();
        $this->dispatch('branch-updated');
    }

    public function confirmDelete(Branch $branch): void
    {
        $this->authorize('delete', $branch);
        $this->deletingBranch = $branch;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingBranch);

        if ($this->deletingBranch->is_main) {
            $this->addError('delete', __('Cannot delete the main branch.'));

            return;
        }

        $this->deletingBranch->delete();
        $this->showDeleteModal = false;
        $this->deletingBranch = null;
        $this->dispatch('branch-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingBranch = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingBranch = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'slug', 'address', 'city', 'state', 'zip',
            'country', 'phone', 'email', 'capacity',
        ]);
        $this->timezone = 'Africa/Accra';
        $this->status = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.branches.branch-index');
    }
}
