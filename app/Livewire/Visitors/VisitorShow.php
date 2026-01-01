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
class VisitorShow extends Component
{
    public Branch $branch;

    public Visitor $visitor;

    public bool $editing = false;

    // Form fields
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $visit_date = null;

    public string $status = '';

    public string $how_did_you_hear = '';

    public string $notes = '';

    public ?string $assigned_to = null;

    // Modals
    public bool $showDeleteModal = false;

    public bool $showConvertModal = false;

    public ?string $convertToMemberId = null;

    public function mount(Branch $branch, Visitor $visitor): void
    {
        $this->authorize('view', $visitor);
        $this->branch = $branch;
        $this->visitor = $visitor;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->visitor);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->visitor);
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
    public function attendanceCount(): int
    {
        return $this->visitor->attendance()->count();
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

    public function edit(): void
    {
        $this->authorize('update', $this->visitor);

        $this->fill([
            'first_name' => $this->visitor->first_name,
            'last_name' => $this->visitor->last_name,
            'email' => $this->visitor->email ?? '',
            'phone' => $this->visitor->phone ?? '',
            'visit_date' => $this->visitor->visit_date?->format('Y-m-d'),
            'status' => $this->visitor->status->value,
            'how_did_you_hear' => $this->visitor->how_did_you_hear ?? '',
            'notes' => $this->visitor->notes ?? '',
            'assigned_to' => $this->visitor->assigned_to,
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->visitor);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['email', 'phone', 'how_did_you_hear', 'notes', 'assigned_to'];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->visitor->update($validated);
        $this->visitor->refresh();

        $this->editing = false;
        $this->dispatch('visitor-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->visitor);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->visitor);
        $this->visitor->delete();
        $this->dispatch('visitor-deleted');
        $this->redirect(route('visitors.index', $this->branch), navigate: true);
    }

    public function openConvertModal(): void
    {
        $this->authorize('update', $this->visitor);
        $this->convertToMemberId = null;
        $this->showConvertModal = true;
    }

    public function cancelConvert(): void
    {
        $this->showConvertModal = false;
        $this->convertToMemberId = null;
    }

    public function convert(): void
    {
        $this->authorize('update', $this->visitor);

        $this->validate([
            'convertToMemberId' => ['required', 'uuid', 'exists:members,id'],
        ]);

        $this->visitor->update([
            'status' => VisitorStatus::Converted->value,
            'is_converted' => true,
            'converted_member_id' => $this->convertToMemberId,
        ]);

        $this->visitor->refresh();
        $this->showConvertModal = false;
        $this->convertToMemberId = null;
        $this->dispatch('visitor-converted');
    }

    public function render()
    {
        return view('livewire.visitors.visitor-show');
    }
}
