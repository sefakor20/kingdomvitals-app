<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpOutcome;
use App\Enums\VisitorStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class VisitorIndex extends Component
{
    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $statusFilter = '';

    public string $convertedFilter = '';

    // Advanced filters
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $assignedMemberFilter = null;

    public string $sourceFilter = '';

    // Bulk selection
    /** @var array<string> */
    public array $selectedVisitors = [];

    public bool $selectAll = false;

    // Bulk action modals
    public bool $showBulkDeleteModal = false;

    public bool $showBulkAssignModal = false;

    public bool $showBulkStatusModal = false;

    public ?string $bulkAssignTo = null;

    public string $bulkStatusValue = '';

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

        if ($this->search !== '' && $this->search !== '0') {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($this->statusFilter !== '' && $this->statusFilter !== '0') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->convertedFilter !== '') {
            $query->where('is_converted', $this->convertedFilter === 'yes');
        }

        // Advanced filters
        if ($this->dateFrom) {
            $query->whereDate('visit_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('visit_date', '<=', $this->dateTo);
        }

        if ($this->assignedMemberFilter !== null) {
            if ($this->assignedMemberFilter === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $this->assignedMemberFilter);
            }
        }

        if ($this->sourceFilter !== '' && $this->sourceFilter !== '0') {
            $query->where('how_did_you_hear', $this->sourceFilter);
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

    #[Computed]
    public function visitorStats(): array
    {
        $visitors = $this->visitors;
        $total = $visitors->count();
        $new = $visitors->where('status', VisitorStatus::New)->count();
        $converted = $visitors->where('is_converted', true)->count();
        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        // Count visitors with pending follow-ups
        $pendingFollowUps = $visitors->filter(function ($visitor) {
            return $visitor->followUps()
                ->where('outcome', FollowUpOutcome::Pending)
                ->exists();
        })->count();

        return [
            'total' => $total,
            'new' => $new,
            'converted' => $converted,
            'conversionRate' => $conversionRate,
            'pendingFollowUps' => $pendingFollowUps,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->statusFilter !== ''
            || $this->convertedFilter !== ''
            || $this->dateFrom !== null
            || $this->dateTo !== null
            || $this->assignedMemberFilter !== null
            || $this->sourceFilter !== '';
    }

    #[Computed]
    public function hasSelection(): bool
    {
        return count($this->selectedVisitors) > 0;
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selectedVisitors);
    }

    #[Computed]
    public function canBulkDelete(): bool
    {
        return $this->hasSelection && $this->canDelete;
    }

    #[Computed]
    public function canBulkUpdate(): bool
    {
        if (! $this->hasSelection) {
            return false;
        }

        // Check if user has a role that can update visitors (Admin, Manager, Staff)
        return auth()->user()->branchAccess()
            ->where('branch_id', $this->branch->id)
            ->whereIn('role', [
                \App\Enums\BranchRole::Admin,
                \App\Enums\BranchRole::Manager,
                \App\Enums\BranchRole::Staff,
            ])
            ->exists();
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

    // ============================================
    // FILTER METHODS
    // ============================================

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'convertedFilter',
            'dateFrom', 'dateTo', 'assignedMemberFilter', 'sourceFilter',
        ]);
        $this->clearSelection();
        unset($this->visitors);
        unset($this->visitorStats);
        unset($this->hasActiveFilters);
    }

    // ============================================
    // CSV EXPORT
    // ============================================

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Visitor::class, $this->branch]);

        $visitors = $this->visitors;

        $filename = sprintf(
            'visitors_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($visitors): void {
            $handle = fopen('php://output', 'w');

            // Headers
            fputcsv($handle, [
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Visit Date',
                'Status',
                'Source',
                'Assigned To',
                'Converted',
                'Notes',
            ]);

            // Data rows
            foreach ($visitors as $visitor) {
                fputcsv($handle, [
                    $visitor->first_name,
                    $visitor->last_name,
                    $visitor->email ?? '',
                    $visitor->phone ?? '',
                    $visitor->visit_date?->format('Y-m-d') ?? '',
                    str_replace('_', ' ', ucfirst($visitor->status->value)),
                    $visitor->how_did_you_hear ?? '',
                    $visitor->assignedMember?->fullName() ?? '',
                    $visitor->is_converted ? 'Yes' : 'No',
                    $visitor->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ============================================
    // BULK SELECTION METHODS
    // ============================================

    public function updatedSelectAll(): void
    {
        $this->selectedVisitors = $this->selectAll ? $this->visitors->pluck('id')->toArray() : [];
    }

    public function updatedSelectedVisitors(): void
    {
        $this->selectAll = count($this->selectedVisitors) === $this->visitors->count()
            && $this->visitors->count() > 0;
    }

    public function clearSelection(): void
    {
        $this->selectedVisitors = [];
        $this->selectAll = false;
    }

    // ============================================
    // BULK DELETE
    // ============================================

    public function confirmBulkDelete(): void
    {
        $this->authorize('deleteAny', [Visitor::class, $this->branch]);
        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete(): void
    {
        $this->authorize('deleteAny', [Visitor::class, $this->branch]);

        $count = Visitor::whereIn('id', $this->selectedVisitors)
            ->where('branch_id', $this->branch->id)
            ->delete();

        $this->clearSelection();
        unset($this->visitors);
        unset($this->visitorStats);

        $this->showBulkDeleteModal = false;
        $this->dispatch('visitors-bulk-deleted', count: $count);
    }

    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
    }

    // ============================================
    // BULK ASSIGN
    // ============================================

    public function openBulkAssignModal(): void
    {
        $this->authorizeBulkUpdate();
        $this->bulkAssignTo = null;
        $this->showBulkAssignModal = true;
    }

    public function bulkAssign(): void
    {
        $this->authorizeBulkUpdate();

        $assignTo = $this->bulkAssignTo === 'unassign' ? null : $this->bulkAssignTo;

        if ($assignTo !== null && $assignTo !== 'unassign') {
            $this->validate([
                'bulkAssignTo' => ['required', 'uuid', 'exists:members,id'],
            ]);
        }

        $count = Visitor::whereIn('id', $this->selectedVisitors)
            ->where('branch_id', $this->branch->id)
            ->update(['assigned_to' => $assignTo]);

        $this->clearSelection();
        unset($this->visitors);

        $this->showBulkAssignModal = false;
        $this->bulkAssignTo = null;
        $this->dispatch('visitors-bulk-assigned', count: $count);
    }

    public function cancelBulkAssign(): void
    {
        $this->showBulkAssignModal = false;
        $this->bulkAssignTo = null;
    }

    // ============================================
    // BULK STATUS CHANGE
    // ============================================

    public function openBulkStatusModal(): void
    {
        $this->authorizeBulkUpdate();
        $this->bulkStatusValue = '';
        $this->showBulkStatusModal = true;
    }

    public function bulkChangeStatus(): void
    {
        $this->authorizeBulkUpdate();

        $this->validate([
            'bulkStatusValue' => ['required', 'string', 'in:new,followed_up,returning,converted,not_interested'],
        ]);

        $updateData = ['status' => $this->bulkStatusValue];

        // If status is 'converted', also set is_converted flag
        if ($this->bulkStatusValue === 'converted') {
            $updateData['is_converted'] = true;
        }

        $count = Visitor::whereIn('id', $this->selectedVisitors)
            ->where('branch_id', $this->branch->id)
            ->update($updateData);

        $this->clearSelection();
        unset($this->visitors);
        unset($this->visitorStats);

        $this->showBulkStatusModal = false;
        $this->bulkStatusValue = '';
        $this->dispatch('visitors-bulk-status-changed', count: $count);
    }

    public function cancelBulkStatus(): void
    {
        $this->showBulkStatusModal = false;
        $this->bulkStatusValue = '';
    }

    /**
     * Authorize bulk update operations.
     * Throws AuthorizationException if user doesn't have permission.
     */
    private function authorizeBulkUpdate(): void
    {
        $canUpdate = auth()->user()->branchAccess()
            ->where('branch_id', $this->branch->id)
            ->whereIn('role', [
                \App\Enums\BranchRole::Admin,
                \App\Enums\BranchRole::Manager,
                \App\Enums\BranchRole::Staff,
            ])
            ->exists();

        if (! $canUpdate) {
            abort(403, 'This action is unauthorized.');
        }
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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.visitors.visitor-index');
    }
}
