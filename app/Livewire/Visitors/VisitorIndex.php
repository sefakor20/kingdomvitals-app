<?php

declare(strict_types=1);

namespace App\Livewire\Visitors;

use App\Enums\FollowUpOutcome;
use App\Enums\QuotaType;
use App\Enums\VisitorStatus;
use App\Exports\VisitorImportTemplateExport;
use App\Imports\VisitorImport;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasQuotaComputed;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Services\PlanAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class VisitorIndex extends Component
{
    use HasFilterableQuery;
    use HasQuotaComputed;
    use WithFileUploads;
    use WithPagination;

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

    // Import properties
    public bool $showImportModal = false;

    public TemporaryUploadedFile|string|null $importFile = null;

    public array $importResults = [];

    public bool $importCompleted = false;

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
    public function visitors(): LengthAwarePaginator
    {
        $query = Visitor::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['first_name', 'last_name', 'email', 'phone']);
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyBooleanFilter($query, 'convertedFilter', 'is_converted', 'yes');
        $this->applyDateRange($query, 'visit_date');
        $this->applyEnumFilter($query, 'sourceFilter', 'how_did_you_hear');

        // Custom assigned member filter with unassigned support
        if ($this->assignedMemberFilter !== null) {
            if ($this->assignedMemberFilter === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $this->assignedMemberFilter);
            }
        }

        return $query->with(['assignedMember', 'convertedMember'])
            ->orderBy('visit_date', 'desc')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedConvertedFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedAssignedMemberFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
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

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return $this->showQuotaWarningFor(QuotaType::Visitors);
    }

    /**
     * Check if visitor creation is allowed based on quota.
     */
    #[Computed]
    public function canCreateWithinQuota(): bool
    {
        return $this->canCreateWithinQuotaFor(QuotaType::Visitors);
    }

    #[Computed]
    public function visitorStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = Visitor::where('branch_id', $this->branch->id);

        // Apply same filters as main query
        $this->applySearch($baseQuery, ['first_name', 'last_name', 'email', 'phone']);
        $this->applyEnumFilter($baseQuery, 'statusFilter', 'status');
        $this->applyBooleanFilter($baseQuery, 'convertedFilter', 'is_converted', 'yes');
        $this->applyDateRange($baseQuery, 'visit_date');
        $this->applyEnumFilter($baseQuery, 'sourceFilter', 'how_did_you_hear');

        if ($this->assignedMemberFilter !== null) {
            if ($this->assignedMemberFilter === 'unassigned') {
                $baseQuery->whereNull('assigned_to');
            } else {
                $baseQuery->where('assigned_to', $this->assignedMemberFilter);
            }
        }

        $total = (clone $baseQuery)->count();
        $new = (clone $baseQuery)->where('status', VisitorStatus::New)->count();
        $converted = (clone $baseQuery)->where('is_converted', true)->count();
        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        // Count visitors with pending follow-ups
        $pendingFollowUps = (clone $baseQuery)->whereHas('followUps', function ($q): void {
            $q->where('outcome', FollowUpOutcome::Pending);
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
        return $this->isFilterActive($this->search)
            || $this->isFilterActive($this->statusFilter)
            || $this->isFilterActive($this->convertedFilter)
            || $this->isFilterActive($this->dateFrom)
            || $this->isFilterActive($this->dateTo)
            || $this->isFilterActive($this->assignedMemberFilter)
            || $this->isFilterActive($this->sourceFilter);
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

        // Check quota before creating
        if (! $this->canCreateWithinQuota) {
            $this->addError('first_name', 'You have reached your visitor limit. Please upgrade your plan to add more visitors.');

            return;
        }

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

        // Build query with same filters but get all records (not paginated)
        $query = Visitor::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['first_name', 'last_name', 'email', 'phone']);
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyBooleanFilter($query, 'convertedFilter', 'is_converted', 'yes');
        $this->applyDateRange($query, 'visit_date');
        $this->applyEnumFilter($query, 'sourceFilter', 'how_did_you_hear');

        if ($this->assignedMemberFilter !== null) {
            if ($this->assignedMemberFilter === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $this->assignedMemberFilter);
            }
        }

        $visitors = $query->with(['assignedMember', 'convertedMember'])
            ->orderBy('visit_date', 'desc')
            ->get();

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
    // CSV IMPORT
    // ============================================

    public function openImportModal(): void
    {
        $this->authorize('create', [Visitor::class, $this->branch]);

        $this->reset(['importFile', 'importResults', 'importCompleted']);
        $this->resetValidation('importFile');
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset(['importFile', 'importResults', 'importCompleted']);
        $this->resetValidation('importFile');
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new VisitorImportTemplateExport,
            'visitor-import-template.xlsx'
        );
    }

    public function processImport(): void
    {
        $this->authorize('create', [Visitor::class, $this->branch]);

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ], [
            'importFile.required' => __('Please select a file to import.'),
            'importFile.mimes' => __('The file must be a CSV or Excel file.'),
            'importFile.max' => __('The file may not be larger than 5MB.'),
        ]);

        // Count rows to check quota
        $rowCount = $this->countImportRows();

        if ($rowCount === 0) {
            $this->addError('importFile', __('The file appears to be empty.'));

            return;
        }

        // Check visitor quota before import
        $quota = $this->visitorQuota;
        if (! $quota['unlimited']) {
            $remaining = max(0, $quota['max'] - $quota['current']);
            if ($rowCount > $remaining) {
                $this->addError('importFile', __('Import would exceed visitor quota. You can import up to :count more visitors.', [
                    'count' => $remaining,
                ]));

                return;
            }
        }

        $import = new VisitorImport($this->branch->id);

        try {
            Excel::import($import, $this->importFile->getRealPath());
        } catch (\Exception $e) {
            $this->addError('importFile', __('Failed to process the import file. Please check the file format.'));

            return;
        }

        $this->importResults = [
            'imported' => $import->getImportedCount(),
            'skipped_duplicates' => count($import->getSkippedDuplicates()),
            'failed' => count($import->failures()),
            'failures' => $import->failures(),
            'duplicates' => $import->getSkippedDuplicates(),
        ];

        $this->importCompleted = true;

        if ($import->getImportedCount() > 0) {
            app(PlanAccessService::class)->invalidateCountCache('visitors');
            unset($this->visitors);
            unset($this->visitorStats);
            unset($this->visitorQuota);
            $this->dispatch('visitors-imported');
        }
    }

    protected function countImportRows(): int
    {
        try {
            $data = Excel::toArray([], $this->importFile->getRealPath());

            return isset($data[0]) ? max(0, count($data[0]) - 1) : 0;
        } catch (\Exception) {
            return 0;
        }
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
