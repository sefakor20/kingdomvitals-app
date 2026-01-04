<?php

namespace App\Livewire\Equipment;

use App\Enums\CheckoutStatus;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentCondition;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\EquipmentCheckout;
use App\Models\Tenant\EquipmentMaintenance;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EquipmentShow extends Component
{
    public Branch $branch;

    public Equipment $equipment;

    public bool $editing = false;

    // Form fields
    public string $name = '';

    public string $category = '';

    public string $description = '';

    public string $serial_number = '';

    public string $model_number = '';

    public string $manufacturer = '';

    public ?string $purchase_date = null;

    public ?string $purchase_price = null;

    public string $currency = 'GHS';

    public string $condition = '';

    public string $location = '';

    public ?string $assigned_to = null;

    public ?string $warranty_expiry = null;

    public ?string $next_maintenance_date = null;

    public string $notes = '';

    // Delete modal
    public bool $showDeleteModal = false;

    // Checkout modal
    public bool $showCheckoutModal = false;

    public string $checkoutMemberId = '';

    public ?string $checkoutExpectedReturn = null;

    public string $checkoutPurpose = '';

    public string $checkoutNotes = '';

    // Return modal
    public bool $showReturnModal = false;

    public ?string $activeCheckoutId = null;

    public string $returnCondition = '';

    public string $returnNotes = '';

    // Maintenance modal
    public bool $showMaintenanceModal = false;

    public string $maintenanceType = '';

    public ?string $maintenanceScheduledDate = null;

    public string $maintenanceDescription = '';

    public string $maintenanceServiceProvider = '';

    public ?string $maintenanceCost = null;

    public function mount(Branch $branch, Equipment $equipment): void
    {
        $this->authorize('view', $equipment);
        $this->branch = $branch;
        $this->equipment = $equipment;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->equipment);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->equipment);
    }

    #[Computed]
    public function canCheckout(): bool
    {
        return auth()->user()->can('checkout', $this->equipment);
    }

    #[Computed]
    public function canManageMaintenance(): bool
    {
        return auth()->user()->can('manageMaintenance', $this->equipment);
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
    public function maintenanceTypes(): array
    {
        return MaintenanceType::cases();
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function checkoutHistory(): Collection
    {
        return $this->equipment->checkouts()
            ->with(['member', 'checkedOutBy', 'checkedInBy'])
            ->latest('checkout_date')
            ->get();
    }

    #[Computed]
    public function maintenanceHistory(): Collection
    {
        return $this->equipment->maintenanceRecords()
            ->with(['requestedBy', 'performedBy'])
            ->latest('scheduled_date')
            ->get();
    }

    #[Computed]
    public function activeCheckout(): ?EquipmentCheckout
    {
        return $this->equipment->activeCheckout;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'condition' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'uuid', 'exists:members,id'],
            'warranty_expiry' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->equipment);

        $this->fill([
            'name' => $this->equipment->name,
            'category' => $this->equipment->category->value,
            'description' => $this->equipment->description ?? '',
            'serial_number' => $this->equipment->serial_number ?? '',
            'model_number' => $this->equipment->model_number ?? '',
            'manufacturer' => $this->equipment->manufacturer ?? '',
            'purchase_date' => $this->equipment->purchase_date?->format('Y-m-d'),
            'purchase_price' => $this->equipment->purchase_price,
            'currency' => $this->equipment->currency ?? 'GHS',
            'condition' => $this->equipment->condition->value,
            'location' => $this->equipment->location ?? '',
            'assigned_to' => $this->equipment->assigned_to,
            'warranty_expiry' => $this->equipment->warranty_expiry?->format('Y-m-d'),
            'next_maintenance_date' => $this->equipment->next_maintenance_date?->format('Y-m-d'),
            'notes' => $this->equipment->notes ?? '',
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->equipment);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'description', 'serial_number', 'model_number', 'manufacturer',
            'purchase_date', 'purchase_price', 'location', 'assigned_to',
            'warranty_expiry', 'next_maintenance_date', 'notes',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->equipment->update($validated);
        $this->equipment->refresh();

        $this->editing = false;
        $this->dispatch('equipment-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->equipment);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->equipment);
        $this->equipment->delete();
        $this->dispatch('equipment-deleted');
        $this->redirect(route('equipment.index', $this->branch), navigate: true);
    }

    // Checkout methods
    public function openCheckoutModal(): void
    {
        $this->authorize('checkout', $this->equipment);

        if (! $this->equipment->isAvailable()) {
            return;
        }

        $this->reset(['checkoutMemberId', 'checkoutExpectedReturn', 'checkoutPurpose', 'checkoutNotes']);
        $this->checkoutExpectedReturn = now()->addDays(7)->format('Y-m-d');
        $this->showCheckoutModal = true;
    }

    public function closeCheckoutModal(): void
    {
        $this->showCheckoutModal = false;
        $this->reset(['checkoutMemberId', 'checkoutExpectedReturn', 'checkoutPurpose', 'checkoutNotes']);
        $this->resetValidation();
    }

    public function processCheckout(): void
    {
        $this->authorize('checkout', $this->equipment);

        $this->validate([
            'checkoutMemberId' => ['required', 'uuid', 'exists:members,id'],
            'checkoutExpectedReturn' => ['required', 'date', 'after:today'],
            'checkoutPurpose' => ['nullable', 'string', 'max:500'],
            'checkoutNotes' => ['nullable', 'string'],
        ]);

        EquipmentCheckout::create([
            'equipment_id' => $this->equipment->id,
            'branch_id' => $this->branch->id,
            'member_id' => $this->checkoutMemberId,
            'checked_out_by' => auth()->id(),
            'status' => CheckoutStatus::Approved,
            'checkout_date' => now(),
            'expected_return_date' => $this->checkoutExpectedReturn,
            'purpose' => $this->checkoutPurpose ?: null,
            'checkout_notes' => $this->checkoutNotes ?: null,
        ]);

        $this->equipment->refresh();
        $this->closeCheckoutModal();
        $this->dispatch('equipment-checked-out');
    }

    // Return methods
    public function openReturnModal(): void
    {
        $this->authorize('checkout', $this->equipment);

        $activeCheckout = $this->activeCheckout;
        if (! $activeCheckout) {
            return;
        }

        $this->activeCheckoutId = $activeCheckout->id;
        $this->returnCondition = $this->equipment->condition->value;
        $this->returnNotes = '';
        $this->showReturnModal = true;
    }

    public function closeReturnModal(): void
    {
        $this->showReturnModal = false;
        $this->reset(['activeCheckoutId', 'returnCondition', 'returnNotes']);
        $this->resetValidation();
    }

    public function processReturn(): void
    {
        $this->authorize('checkout', $this->equipment);

        $this->validate([
            'returnCondition' => ['required', 'string'],
            'returnNotes' => ['nullable', 'string'],
        ]);

        $checkout = EquipmentCheckout::findOrFail($this->activeCheckoutId);

        $checkout->update([
            'status' => CheckoutStatus::Returned,
            'actual_return_date' => now(),
            'checked_in_by' => auth()->id(),
            'return_condition' => $this->returnCondition,
            'return_notes' => $this->returnNotes ?: null,
        ]);

        // Update equipment condition
        $this->equipment->update([
            'condition' => $this->returnCondition,
        ]);

        $this->equipment->refresh();
        $this->closeReturnModal();
        $this->dispatch('equipment-returned');
    }

    // Maintenance methods
    public function openMaintenanceModal(): void
    {
        $this->authorize('manageMaintenance', $this->equipment);

        $this->reset([
            'maintenanceType', 'maintenanceScheduledDate', 'maintenanceDescription',
            'maintenanceServiceProvider', 'maintenanceCost',
        ]);
        $this->maintenanceScheduledDate = now()->format('Y-m-d');
        $this->showMaintenanceModal = true;
    }

    public function closeMaintenanceModal(): void
    {
        $this->showMaintenanceModal = false;
        $this->reset([
            'maintenanceType', 'maintenanceScheduledDate', 'maintenanceDescription',
            'maintenanceServiceProvider', 'maintenanceCost',
        ]);
        $this->resetValidation();
    }

    public function scheduleMaintenance(): void
    {
        $this->authorize('manageMaintenance', $this->equipment);

        $this->validate([
            'maintenanceType' => ['required', 'string'],
            'maintenanceScheduledDate' => ['required', 'date'],
            'maintenanceDescription' => ['required', 'string'],
            'maintenanceServiceProvider' => ['nullable', 'string', 'max:255'],
            'maintenanceCost' => ['nullable', 'numeric', 'min:0'],
        ]);

        EquipmentMaintenance::create([
            'equipment_id' => $this->equipment->id,
            'branch_id' => $this->branch->id,
            'requested_by' => auth()->id(),
            'type' => $this->maintenanceType,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_date' => $this->maintenanceScheduledDate,
            'description' => $this->maintenanceDescription,
            'service_provider' => $this->maintenanceServiceProvider ?: null,
            'cost' => $this->maintenanceCost ?: null,
            'currency' => 'GHS',
        ]);

        // Update next maintenance date on equipment
        $this->equipment->update([
            'next_maintenance_date' => $this->maintenanceScheduledDate,
        ]);

        $this->equipment->refresh();
        $this->closeMaintenanceModal();
        $this->dispatch('maintenance-scheduled');
    }

    public function completeMaintenance(string $maintenanceId): void
    {
        $this->authorize('manageMaintenance', $this->equipment);

        $maintenance = EquipmentMaintenance::findOrFail($maintenanceId);

        $maintenance->update([
            'status' => MaintenanceStatus::Completed,
            'completed_date' => now(),
            'performed_by' => auth()->id(),
        ]);

        // Update equipment's last maintenance date
        $this->equipment->update([
            'last_maintenance_date' => now(),
        ]);

        $this->equipment->refresh();
        $this->dispatch('maintenance-completed');
    }

    public function cancelMaintenance(string $maintenanceId): void
    {
        $this->authorize('manageMaintenance', $this->equipment);

        $maintenance = EquipmentMaintenance::findOrFail($maintenanceId);

        $maintenance->update([
            'status' => MaintenanceStatus::Cancelled,
        ]);

        $this->equipment->refresh();
        $this->dispatch('maintenance-cancelled');
    }

    public function render()
    {
        return view('livewire.equipment.equipment-show');
    }
}
