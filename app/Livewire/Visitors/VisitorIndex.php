<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\VisitorStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class VisitorIndex extends Component
{
    public Branch $branch;

    public string $search = '';

    public string $statusFilter = '';

    public string $convertedFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showConvertModal = false;

    // Form properties
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $visit_date = null;

    public string $status = 'new';

    public string $how_did_you_hear = '';

    public string $notes = '';

    public ?string $assigned_to = null;

    public ?Visitor $editingVisitor = null;

    public ?Visitor $deletingVisitor = null;

    public ?Visitor $convertingVisitor = null;

    public ?string $convertToMemberId = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Visitor::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function visitors(): Collection
    {
        $query = Visitor::where('branch_id', $this->branch->id);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->convertedFilter !== '') {
            $query->where('is_converted', $this->convertedFilter === 'yes');
        }

        return $query->with(['assignedMember', 'convertedMember'])
            ->orderBy('visit_date', 'desc')
            ->get();
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
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Visitor::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Visitor::class, $this->branch]);
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

    public function create(): void
    {
        $this->authorize('create', [Visitor::class, $this->branch]);
        $this->resetForm();
        $this->visit_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Visitor::class, $this->branch]);
        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;

        // Convert empty strings to null for nullable fields
        $nullableFields = ['email', 'phone', 'how_did_you_hear', 'notes', 'assigned_to'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        Visitor::create($validated);

        unset($this->visitors); // Clear computed cache

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('visitor-created');
    }

    public function edit(Visitor $visitor): void
    {
        $this->authorize('update', $visitor);
        $this->editingVisitor = $visitor;
        $this->fill([
            'first_name' => $visitor->first_name,
            'last_name' => $visitor->last_name,
            'email' => $visitor->email ?? '',
            'phone' => $visitor->phone ?? '',
            'visit_date' => $visitor->visit_date?->format('Y-m-d'),
            'status' => $visitor->status->value,
            'how_did_you_hear' => $visitor->how_did_you_hear ?? '',
            'notes' => $visitor->notes ?? '',
            'assigned_to' => $visitor->assigned_to,
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingVisitor);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['email', 'phone', 'how_did_you_hear', 'notes', 'assigned_to'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingVisitor->update($validated);

        unset($this->visitors); // Clear computed cache

        $this->showEditModal = false;
        $this->editingVisitor = null;
        $this->resetForm();
        $this->dispatch('visitor-updated');
    }

    public function confirmDelete(Visitor $visitor): void
    {
        $this->authorize('delete', $visitor);
        $this->deletingVisitor = $visitor;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingVisitor);

        $this->deletingVisitor->delete();

        unset($this->visitors); // Clear computed cache

        $this->showDeleteModal = false;
        $this->deletingVisitor = null;
        $this->dispatch('visitor-deleted');
    }

    public function openConvertModal(Visitor $visitor): void
    {
        $this->authorize('update', $visitor);
        $this->convertingVisitor = $visitor;
        $this->convertToMemberId = null;
        $this->showConvertModal = true;
    }

    public function convert(): void
    {
        $this->authorize('update', $this->convertingVisitor);

        $this->validate([
            'convertToMemberId' => ['required', 'uuid', 'exists:members,id'],
        ]);

        $this->convertingVisitor->update([
            'status' => VisitorStatus::Converted->value,
            'is_converted' => true,
            'converted_member_id' => $this->convertToMemberId,
        ]);

        unset($this->visitors); // Clear computed cache

        $this->showConvertModal = false;
        $this->convertingVisitor = null;
        $this->convertToMemberId = null;
        $this->dispatch('visitor-converted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingVisitor = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingVisitor = null;
    }

    public function cancelConvert(): void
    {
        $this->showConvertModal = false;
        $this->convertingVisitor = null;
        $this->convertToMemberId = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'first_name', 'last_name', 'email', 'phone',
            'visit_date', 'how_did_you_hear', 'notes', 'assigned_to',
        ]);
        $this->status = 'new';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.visitors.visitor-index');
    }
}
