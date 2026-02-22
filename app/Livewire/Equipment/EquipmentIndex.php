<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Enums\CheckoutStatus;
use App\Enums\Currency;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\QuotaType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasQuotaComputed;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\EquipmentCheckout;
use App\Models\Tenant\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class EquipmentIndex extends Component
{
    use HasFilterableQuery;
    use HasQuotaComputed;
    use WithPagination;

    public Branch $branch;

    // Search and filters
    public string $search = '';

    public string $categoryFilter = '';

    public string $conditionFilter = '';

    public string $availabilityFilter = '';

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showCheckoutModal = false;

    public bool $showReturnModal = false;

    // Form properties
    public string $name = '';

    public string $category = '';

    public string $description = '';

    public string $serial_number = '';

    public string $model_number = '';

    public string $manufacturer = '';

    public ?string $purchase_date = null;

    public string $purchase_price = '';

    public string $source_of_equipment = '';

    public string $condition = 'good';

    public string $location = '';

    public ?string $assigned_to = null;

    public ?string $warranty_expiry = null;

    public ?string $next_maintenance_date = null;

    public string $notes = '';

    // Checkout form properties
    public ?string $checkout_member_id = null;

    public ?string $checkout_date = null;

    public ?string $expected_return_date = null;

    public string $checkout_purpose = '';

    public string $checkout_notes = '';

    // Return form properties
    public string $return_condition = '';

    public string $return_notes = '';

    public ?Equipment $editingEquipment = null;

    public ?Equipment $deletingEquipment = null;

    public ?Equipment $checkingOutEquipment = null;

    public ?Equipment $returningEquipment = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Equipment::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function currency(): Currency
    {
        return tenant()->getCurrency();
    }

    #[Computed]
    public function equipment(): LengthAwarePaginator
    {
        $query = Equipment::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'description', 'serial_number', 'manufacturer', 'location']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'conditionFilter', 'condition');

        // Custom availability filter with complex logic
        if ($this->isFilterActive($this->availabilityFilter)) {
            if ($this->availabilityFilter === 'available') {
                $query->where('condition', '!=', EquipmentCondition::OutOfService->value)
                    ->whereDoesntHave('activeCheckout');
            } elseif ($this->availabilityFilter === 'checked_out') {
                $query->whereHas('activeCheckout');
            } elseif ($this->availabilityFilter === 'out_of_service') {
                $query->where('condition', EquipmentCondition::OutOfService->value);
            }
        }

        return $query->with(['assignedMember', 'activeCheckout.member'])
            ->orderBy('name')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedConditionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAvailabilityFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories(): array
    {
        return EquipmentCategory::cases();
    }

    #[Computed]
    public function conditions(): array
    {
        return EquipmentCondition::cases();
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
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Equipment::class, $this->branch]);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('deleteAny', [Equipment::class, $this->branch]);
    }

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return $this->showQuotaWarningFor(QuotaType::Equipment);
    }

    /**
     * Check if equipment creation is allowed based on quota.
     */
    #[Computed]
    public function canCreateWithinQuota(): bool
    {
        return $this->canCreateWithinQuotaFor(QuotaType::Equipment);
    }

    #[Computed]
    public function equipmentStats(): array
    {
        // Query database directly for stats (not from paginated collection)
        $baseQuery = Equipment::where('branch_id', $this->branch->id);

        $this->applySearch($baseQuery, ['name', 'description', 'serial_number', 'manufacturer', 'location']);
        $this->applyEnumFilter($baseQuery, 'categoryFilter', 'category');
        $this->applyEnumFilter($baseQuery, 'conditionFilter', 'condition');

        if ($this->isFilterActive($this->availabilityFilter)) {
            if ($this->availabilityFilter === 'available') {
                $baseQuery->where('condition', '!=', EquipmentCondition::OutOfService->value)
                    ->whereDoesntHave('activeCheckout');
            } elseif ($this->availabilityFilter === 'checked_out') {
                $baseQuery->whereHas('activeCheckout');
            } elseif ($this->availabilityFilter === 'out_of_service') {
                $baseQuery->where('condition', EquipmentCondition::OutOfService->value);
            }
        }

        $total = (clone $baseQuery)->count();
        $available = (clone $baseQuery)
            ->where('condition', '!=', EquipmentCondition::OutOfService->value)
            ->whereDoesntHave('activeCheckout')
            ->count();
        $checkedOut = (clone $baseQuery)->whereHas('activeCheckout')->count();
        $outOfService = (clone $baseQuery)->where('condition', EquipmentCondition::OutOfService->value)->count();
        $maintenanceDue = (clone $baseQuery)
            ->whereNotNull('next_maintenance_date')
            ->where('next_maintenance_date', '<=', now())
            ->count();
        $totalValue = (clone $baseQuery)->sum('purchase_price');

        return [
            'total' => $total,
            'available' => $available,
            'checkedOut' => $checkedOut,
            'outOfService' => $outOfService,
            'maintenanceDue' => $maintenanceDue,
            'totalValue' => $totalValue,
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        if ($this->isFilterActive($this->search)) {
            return true;
        }
        if ($this->isFilterActive($this->categoryFilter)) {
            return true;
        }
        if ($this->isFilterActive($this->conditionFilter)) {
            return true;
        }

        return $this->isFilterActive($this->availabilityFilter);
    }

    protected function rules(): array
    {
        $categories = collect(EquipmentCategory::cases())->pluck('value')->implode(',');
        $conditions = collect(EquipmentCondition::cases())->pluck('value')->implode(',');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.$categories],
            'description' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'source_of_equipment' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'string', 'in:'.$conditions],
            'location' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'uuid', 'exists:members,id'],
            'warranty_expiry' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Equipment::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Equipment::class, $this->branch]);

        // Check quota before creating
        if (! $this->canCreateWithinQuota) {
            $this->addError('name', 'You have reached your equipment limit. Please upgrade your plan to add more equipment.');

            return;
        }

        $validated = $this->validate();

        $validated['branch_id'] = $this->branch->id;
        $validated['currency'] = tenant()->getCurrencyCode();

        // Convert empty strings to null
        foreach (['description', 'serial_number', 'model_number', 'manufacturer', 'purchase_date', 'purchase_price', 'source_of_equipment', 'location', 'assigned_to', 'warranty_expiry', 'next_maintenance_date', 'notes'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        Equipment::create($validated);

        unset($this->equipment);
        unset($this->equipmentStats);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('equipment-created');
    }

    public function edit(Equipment $equipment): void
    {
        $this->authorize('update', $equipment);
        $this->editingEquipment = $equipment;
        $this->fill([
            'name' => $equipment->name,
            'category' => $equipment->category->value,
            'description' => $equipment->description ?? '',
            'serial_number' => $equipment->serial_number ?? '',
            'model_number' => $equipment->model_number ?? '',
            'manufacturer' => $equipment->manufacturer ?? '',
            'purchase_date' => $equipment->purchase_date?->format('Y-m-d'),
            'purchase_price' => $equipment->purchase_price ? (string) $equipment->purchase_price : '',
            'source_of_equipment' => $equipment->source_of_equipment ?? '',
            'condition' => $equipment->condition->value,
            'location' => $equipment->location ?? '',
            'assigned_to' => $equipment->assigned_to,
            'warranty_expiry' => $equipment->warranty_expiry?->format('Y-m-d'),
            'next_maintenance_date' => $equipment->next_maintenance_date?->format('Y-m-d'),
            'notes' => $equipment->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingEquipment);
        $validated = $this->validate();

        // Convert empty strings to null
        foreach (['description', 'serial_number', 'model_number', 'manufacturer', 'purchase_date', 'purchase_price', 'source_of_equipment', 'location', 'assigned_to', 'warranty_expiry', 'next_maintenance_date', 'notes'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->editingEquipment->update($validated);

        unset($this->equipment);
        unset($this->equipmentStats);

        $this->showEditModal = false;
        $this->editingEquipment = null;
        $this->resetForm();
        $this->dispatch('equipment-updated');
    }

    public function confirmDelete(Equipment $equipment): void
    {
        $this->authorize('delete', $equipment);
        $this->deletingEquipment = $equipment;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingEquipment);

        $this->deletingEquipment->delete();

        unset($this->equipment);
        unset($this->equipmentStats);

        $this->showDeleteModal = false;
        $this->deletingEquipment = null;
        $this->dispatch('equipment-deleted');
    }

    public function openCheckoutModal(Equipment $equipment): void
    {
        $this->authorize('checkout', $equipment);

        if (! $equipment->isAvailable()) {
            return;
        }

        $this->checkingOutEquipment = $equipment;
        $this->checkout_date = now()->format('Y-m-d\TH:i');
        $this->expected_return_date = now()->addDays(7)->format('Y-m-d\TH:i');
        $this->checkout_member_id = null;
        $this->checkout_purpose = '';
        $this->checkout_notes = '';
        $this->showCheckoutModal = true;
    }

    public function processCheckout(): void
    {
        $this->authorize('checkout', $this->checkingOutEquipment);

        $this->validate([
            'checkout_member_id' => ['required', 'uuid', 'exists:members,id'],
            'checkout_date' => ['required', 'date'],
            'expected_return_date' => ['required', 'date', 'after:checkout_date'],
            'checkout_purpose' => ['nullable', 'string'],
            'checkout_notes' => ['nullable', 'string'],
        ]);

        EquipmentCheckout::create([
            'equipment_id' => $this->checkingOutEquipment->id,
            'branch_id' => $this->branch->id,
            'member_id' => $this->checkout_member_id,
            'checked_out_by' => auth()->id(),
            'status' => CheckoutStatus::Approved,
            'checkout_date' => $this->checkout_date,
            'expected_return_date' => $this->expected_return_date,
            'purpose' => $this->checkout_purpose ?: null,
            'checkout_notes' => $this->checkout_notes ?: null,
        ]);

        unset($this->equipment);
        unset($this->equipmentStats);

        $this->showCheckoutModal = false;
        $this->checkingOutEquipment = null;
        $this->resetCheckoutForm();
        $this->dispatch('equipment-checked-out');
    }

    public function openReturnModal(Equipment $equipment): void
    {
        $this->authorize('processReturn', $equipment);

        if (! $equipment->isCheckedOut()) {
            return;
        }

        $this->returningEquipment = $equipment;
        $this->return_condition = $equipment->condition->value;
        $this->return_notes = '';
        $this->showReturnModal = true;
    }

    public function processReturn(): void
    {
        $this->authorize('processReturn', $this->returningEquipment);

        $conditions = collect(EquipmentCondition::cases())->pluck('value')->implode(',');

        $this->validate([
            'return_condition' => ['required', 'string', 'in:'.$conditions],
            'return_notes' => ['nullable', 'string'],
        ]);

        $activeCheckout = $this->returningEquipment->activeCheckout;

        if ($activeCheckout) {
            $activeCheckout->update([
                'status' => CheckoutStatus::Returned,
                'actual_return_date' => now(),
                'return_condition' => $this->return_condition,
                'return_notes' => $this->return_notes ?: null,
                'checked_in_by' => auth()->id(),
            ]);

            // Update equipment condition if changed
            if ($this->returningEquipment->condition->value !== $this->return_condition) {
                $this->returningEquipment->update(['condition' => $this->return_condition]);
            }
        }

        unset($this->equipment);
        unset($this->equipmentStats);

        $this->showReturnModal = false;
        $this->returningEquipment = null;
        $this->return_condition = '';
        $this->return_notes = '';
        $this->dispatch('equipment-returned');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingEquipment = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingEquipment = null;
    }

    public function cancelCheckout(): void
    {
        $this->showCheckoutModal = false;
        $this->checkingOutEquipment = null;
        $this->resetCheckoutForm();
    }

    public function cancelReturn(): void
    {
        $this->showReturnModal = false;
        $this->returningEquipment = null;
        $this->return_condition = '';
        $this->return_notes = '';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'conditionFilter', 'availabilityFilter']);
        $this->resetPage();
        unset($this->equipment);
        unset($this->equipmentStats);
        unset($this->hasActiveFilters);
    }

    public function exportToCsv(): StreamedResponse
    {
        $this->authorize('viewAny', [Equipment::class, $this->branch]);

        // Build query with same filters but get all records (not paginated)
        $query = Equipment::where('branch_id', $this->branch->id);

        $this->applySearch($query, ['name', 'description', 'serial_number', 'manufacturer', 'location']);
        $this->applyEnumFilter($query, 'categoryFilter', 'category');
        $this->applyEnumFilter($query, 'conditionFilter', 'condition');

        if ($this->isFilterActive($this->availabilityFilter)) {
            if ($this->availabilityFilter === 'available') {
                $query->where('condition', '!=', EquipmentCondition::OutOfService->value)
                    ->whereDoesntHave('activeCheckout');
            } elseif ($this->availabilityFilter === 'checked_out') {
                $query->whereHas('activeCheckout');
            } elseif ($this->availabilityFilter === 'out_of_service') {
                $query->where('condition', EquipmentCondition::OutOfService->value);
            }
        }

        $equipment = $query->with(['assignedMember', 'activeCheckout.member'])
            ->orderBy('name')
            ->get();

        $filename = sprintf(
            'equipment_%s_%s.csv',
            str($this->branch->name)->slug(),
            now()->format('Y-m-d_His')
        );

        return response()->streamDownload(function () use ($equipment): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Category',
                'Condition',
                'Serial Number',
                'Model',
                'Manufacturer',
                'Location',
                'Purchase Date',
                'Purchase Price',
                'Status',
                'Assigned To',
                'Warranty Expiry',
                'Next Maintenance',
            ]);

            foreach ($equipment as $item) {
                $status = 'Available';
                if ($item->isOutOfService()) {
                    $status = 'Out of Service';
                } elseif ($item->isCheckedOut()) {
                    $status = 'Checked Out';
                }

                fputcsv($handle, [
                    $item->name,
                    ucfirst($item->category->value),
                    ucfirst(str_replace('_', ' ', $item->condition->value)),
                    $item->serial_number ?? '-',
                    $item->model_number ?? '-',
                    $item->manufacturer ?? '-',
                    $item->location ?? '-',
                    $item->purchase_date?->format('Y-m-d') ?? '-',
                    $item->purchase_price ? number_format((float) $item->purchase_price, 2) : '-',
                    $status,
                    $item->assignedMember?->fullName() ?? '-',
                    $item->warranty_expiry?->format('Y-m-d') ?? '-',
                    $item->next_maintenance_date?->format('Y-m-d') ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'category', 'description', 'serial_number', 'model_number',
            'manufacturer', 'purchase_date', 'purchase_price', 'source_of_equipment', 'location',
            'assigned_to', 'warranty_expiry', 'next_maintenance_date', 'notes',
        ]);
        $this->condition = 'good';
        $this->resetValidation();
    }

    private function resetCheckoutForm(): void
    {
        $this->reset([
            'checkout_member_id', 'checkout_date', 'expected_return_date',
            'checkout_purpose', 'checkout_notes',
        ]);
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.equipment.equipment-index');
    }
}
