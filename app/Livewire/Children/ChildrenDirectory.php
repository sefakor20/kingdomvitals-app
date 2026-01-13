<?php

declare(strict_types=1);

namespace App\Livewire\Children;

use App\Models\Tenant\AgeGroup;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChildEmergencyContact;
use App\Models\Tenant\ChildMedicalInfo;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ChildrenDirectory extends Component
{
    public Branch $branch;

    // Filters
    public string $search = '';

    public string $ageGroupFilter = '';

    public string $householdFilter = '';

    public ?int $minAge = null;

    public ?int $maxAge = null;

    // Modals
    public bool $showAssignAgeGroupModal = false;

    public bool $showEmergencyContactsModal = false;

    public bool $showMedicalInfoModal = false;

    public bool $showAddContactModal = false;

    public bool $showEditContactModal = false;

    public bool $showDeleteContactModal = false;

    public bool $showCreateChildModal = false;

    public bool $showEditChildModal = false;

    // Selected child
    public ?Member $selectedChild = null;

    public ?Member $editingChild = null;

    // Child form properties
    public string $firstName = '';

    public string $lastName = '';

    public string $middleName = '';

    public ?string $childDateOfBirth = null;

    public string $childGender = '';

    public ?string $childHouseholdId = null;

    public ?string $childAgeGroupId = null;

    // Age group assignment
    public ?string $selectedAgeGroupId = null;

    // Emergency Contact form
    public string $contactName = '';

    public string $contactRelationship = '';

    public string $contactPhone = '';

    public string $contactPhoneSecondary = '';

    public string $contactEmail = '';

    public bool $contactIsPrimary = false;

    public bool $contactCanPickup = true;

    public string $contactNotes = '';

    public ?ChildEmergencyContact $editingContact = null;

    public ?ChildEmergencyContact $deletingContact = null;

    // Medical Info form
    public string $allergies = '';

    public string $medicalConditions = '';

    public string $medications = '';

    public string $specialNeeds = '';

    public string $dietaryRestrictions = '';

    public string $bloodType = '';

    public string $doctorName = '';

    public string $doctorPhone = '';

    public string $insuranceInfo = '';

    public string $emergencyInstructions = '';

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Member::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function children(): Collection
    {
        $query = Member::query()
            ->where('primary_branch_id', $this->branch->id)
            ->children()
            ->with(['household', 'ageGroup', 'emergencyContacts', 'medicalInfo']);

        if ($this->search !== '' && $this->search !== '0') {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($this->ageGroupFilter !== '' && $this->ageGroupFilter !== '0') {
            if ($this->ageGroupFilter === 'unassigned') {
                $query->whereNull('age_group_id');
            } else {
                $query->where('age_group_id', $this->ageGroupFilter);
            }
        }

        if ($this->householdFilter !== '' && $this->householdFilter !== '0') {
            $query->where('household_id', $this->householdFilter);
        }

        if ($this->minAge !== null) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$this->minAge]);
        }

        if ($this->maxAge !== null) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$this->maxAge]);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    #[Computed]
    public function ageGroups(): Collection
    {
        return AgeGroup::query()
            ->where('branch_id', $this->branch->id)
            ->active()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function allAgeGroups(): Collection
    {
        return AgeGroup::query()
            ->where('branch_id', $this->branch->id)
            ->ordered()
            ->get();
    }

    #[Computed]
    public function households(): Collection
    {
        return Household::query()
            ->where('branch_id', $this->branch->id)
            ->has('children')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $baseQuery = Member::where('primary_branch_id', $this->branch->id)->children();

        return [
            'total' => (clone $baseQuery)->count(),
            'unassigned' => (clone $baseQuery)->whereNull('age_group_id')->count(),
            'withEmergencyContact' => (clone $baseQuery)->whereHas('emergencyContacts')->count(),
            'withMedicalInfo' => (clone $baseQuery)->whereHas('medicalInfo')->count(),
        ];
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->ageGroupFilter !== ''
            || $this->householdFilter !== ''
            || $this->minAge !== null
            || $this->maxAge !== null;
    }

    #[Computed]
    public function canCreateChild(): bool
    {
        return auth()->user()?->can('create', [Member::class, $this->branch]) ?? false;
    }

    #[Computed]
    public function allHouseholds(): Collection
    {
        return Household::query()
            ->where('branch_id', $this->branch->id)
            ->orderBy('name')
            ->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'ageGroupFilter', 'householdFilter', 'minAge', 'maxAge']);
        unset($this->children);
    }

    // Child Creation & Editing
    protected function childRules(): array
    {
        now()->format('Y-m-d');
        $minDate = now()->subYears(17)->format('Y-m-d');

        return [
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
            'childDateOfBirth' => ['required', 'date', 'before_or_equal:today', 'after:'.$minDate],
            'childGender' => ['nullable', 'string', 'in:male,female'],
            'childHouseholdId' => ['nullable', 'uuid', 'exists:households,id'],
            'childAgeGroupId' => ['nullable', 'uuid', 'exists:age_groups,id'],
        ];
    }

    public function createChild(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);
        $this->resetChildForm();
        $this->showCreateChildModal = true;
    }

    public function storeChild(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);
        $this->validate($this->childRules());

        $child = Member::create([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName ?: null,
            'date_of_birth' => $this->childDateOfBirth,
            'gender' => $this->childGender ?: null,
            'household_id' => $this->childHouseholdId ?: null,
            'age_group_id' => $this->childAgeGroupId ?: null,
            'primary_branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        if (! $this->childAgeGroupId) {
            $child->assignAgeGroupByAge();
        }

        unset($this->children);
        unset($this->stats);

        $this->showCreateChildModal = false;
        $this->resetChildForm();
        $this->dispatch('child-created');
    }

    public function cancelCreateChild(): void
    {
        $this->showCreateChildModal = false;
        $this->resetChildForm();
    }

    public function editChild(Member $child): void
    {
        $this->authorize('update', $child);
        $this->editingChild = $child;
        $this->fill([
            'firstName' => $child->first_name,
            'lastName' => $child->last_name,
            'middleName' => $child->middle_name ?? '',
            'childDateOfBirth' => $child->date_of_birth?->format('Y-m-d'),
            'childGender' => $child->gender?->value ?? '',
            'childHouseholdId' => $child->household_id,
            'childAgeGroupId' => $child->age_group_id,
        ]);
        $this->showEditChildModal = true;
    }

    public function updateChild(): void
    {
        $this->authorize('update', $this->editingChild);
        $this->validate($this->childRules());

        $this->editingChild->update([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName ?: null,
            'date_of_birth' => $this->childDateOfBirth,
            'gender' => $this->childGender ?: null,
            'household_id' => $this->childHouseholdId ?: null,
            'age_group_id' => $this->childAgeGroupId ?: null,
        ]);

        if (! $this->childAgeGroupId && $this->editingChild->wasChanged('date_of_birth')) {
            $this->editingChild->assignAgeGroupByAge();
        }

        unset($this->children);
        unset($this->stats);

        $this->showEditChildModal = false;
        $this->editingChild = null;
        $this->resetChildForm();
        $this->dispatch('child-updated');
    }

    public function cancelEditChild(): void
    {
        $this->showEditChildModal = false;
        $this->editingChild = null;
        $this->resetChildForm();
    }

    protected function resetChildForm(): void
    {
        $this->reset([
            'firstName', 'lastName', 'middleName',
            'childDateOfBirth', 'childGender',
            'childHouseholdId', 'childAgeGroupId',
        ]);
        $this->resetValidation();
    }

    // Age Group Assignment
    public function openAssignAgeGroupModal(Member $child): void
    {
        $this->selectedChild = $child;
        $this->selectedAgeGroupId = $child->age_group_id;
        $this->showAssignAgeGroupModal = true;
    }

    public function assignAgeGroup(): void
    {
        $this->authorize('update', $this->selectedChild);

        $this->selectedChild->update([
            'age_group_id' => $this->selectedAgeGroupId ?: null,
        ]);

        unset($this->children);
        unset($this->stats);

        $this->showAssignAgeGroupModal = false;
        $this->selectedChild = null;
        $this->selectedAgeGroupId = null;
        $this->dispatch('age-group-assigned');
    }

    public function autoAssignAgeGroup(Member $child): void
    {
        $this->authorize('update', $child);
        $child->assignAgeGroupByAge();

        unset($this->children);
        unset($this->stats);

        $this->dispatch('age-group-auto-assigned');
    }

    public function cancelAssignAgeGroup(): void
    {
        $this->showAssignAgeGroupModal = false;
        $this->selectedChild = null;
        $this->selectedAgeGroupId = null;
    }

    // Emergency Contacts Management
    public function openEmergencyContactsModal(Member $child): void
    {
        $this->selectedChild = $child->load('emergencyContacts');
        $this->resetContactForm();
        $this->showEmergencyContactsModal = true;
    }

    public function closeEmergencyContactsModal(): void
    {
        $this->showEmergencyContactsModal = false;
        $this->selectedChild = null;
        $this->resetContactForm();
    }

    public function openAddContactModal(): void
    {
        $this->resetContactForm();
        $this->showAddContactModal = true;
    }

    public function addEmergencyContact(): void
    {
        $this->authorize('update', $this->selectedChild);

        $this->validate([
            'contactName' => ['required', 'string', 'max:100'],
            'contactRelationship' => ['required', 'string', 'max:50'],
            'contactPhone' => ['required', 'string', 'max:20'],
            'contactPhoneSecondary' => ['nullable', 'string', 'max:20'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactNotes' => ['nullable', 'string'],
        ]);

        // If marking as primary, unset other primary contacts
        if ($this->contactIsPrimary) {
            $this->selectedChild->emergencyContacts()->update(['is_primary' => false]);
        }

        $this->selectedChild->emergencyContacts()->create([
            'name' => $this->contactName,
            'relationship' => $this->contactRelationship,
            'phone' => $this->contactPhone,
            'phone_secondary' => $this->contactPhoneSecondary ?: null,
            'email' => $this->contactEmail ?: null,
            'is_primary' => $this->contactIsPrimary,
            'can_pickup' => $this->contactCanPickup,
            'notes' => $this->contactNotes ?: null,
        ]);

        $this->showAddContactModal = false;
        $this->resetContactForm();
        $this->selectedChild->refresh();
        unset($this->children);
        $this->dispatch('contact-added');
    }

    public function editContact(ChildEmergencyContact $contact): void
    {
        $this->editingContact = $contact;
        $this->fill([
            'contactName' => $contact->name,
            'contactRelationship' => $contact->relationship,
            'contactPhone' => $contact->phone,
            'contactPhoneSecondary' => $contact->phone_secondary ?? '',
            'contactEmail' => $contact->email ?? '',
            'contactIsPrimary' => $contact->is_primary,
            'contactCanPickup' => $contact->can_pickup,
            'contactNotes' => $contact->notes ?? '',
        ]);
        $this->showEditContactModal = true;
    }

    public function updateContact(): void
    {
        $this->authorize('update', $this->selectedChild);

        $this->validate([
            'contactName' => ['required', 'string', 'max:100'],
            'contactRelationship' => ['required', 'string', 'max:50'],
            'contactPhone' => ['required', 'string', 'max:20'],
            'contactPhoneSecondary' => ['nullable', 'string', 'max:20'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactNotes' => ['nullable', 'string'],
        ]);

        // If marking as primary, unset other primary contacts
        if ($this->contactIsPrimary && ! $this->editingContact->is_primary) {
            $this->selectedChild->emergencyContacts()->update(['is_primary' => false]);
        }

        $this->editingContact->update([
            'name' => $this->contactName,
            'relationship' => $this->contactRelationship,
            'phone' => $this->contactPhone,
            'phone_secondary' => $this->contactPhoneSecondary ?: null,
            'email' => $this->contactEmail ?: null,
            'is_primary' => $this->contactIsPrimary,
            'can_pickup' => $this->contactCanPickup,
            'notes' => $this->contactNotes ?: null,
        ]);

        $this->showEditContactModal = false;
        $this->editingContact = null;
        $this->resetContactForm();
        $this->selectedChild->refresh();
        unset($this->children);
        $this->dispatch('contact-updated');
    }

    public function cancelEditContact(): void
    {
        $this->showEditContactModal = false;
        $this->editingContact = null;
        $this->resetContactForm();
    }

    public function confirmDeleteContact(ChildEmergencyContact $contact): void
    {
        $this->deletingContact = $contact;
        $this->showDeleteContactModal = true;
    }

    public function deleteContact(): void
    {
        $this->authorize('update', $this->selectedChild);
        $this->deletingContact->delete();

        $this->showDeleteContactModal = false;
        $this->deletingContact = null;
        $this->selectedChild->refresh();
        unset($this->children);
        $this->dispatch('contact-deleted');
    }

    public function cancelDeleteContact(): void
    {
        $this->showDeleteContactModal = false;
        $this->deletingContact = null;
    }

    // Medical Info Management
    public function openMedicalInfoModal(Member $child): void
    {
        $this->selectedChild = $child->load('medicalInfo');

        if ($child->medicalInfo) {
            $this->fill([
                'allergies' => $child->medicalInfo->allergies ?? '',
                'medicalConditions' => $child->medicalInfo->medical_conditions ?? '',
                'medications' => $child->medicalInfo->medications ?? '',
                'specialNeeds' => $child->medicalInfo->special_needs ?? '',
                'dietaryRestrictions' => $child->medicalInfo->dietary_restrictions ?? '',
                'bloodType' => $child->medicalInfo->blood_type ?? '',
                'doctorName' => $child->medicalInfo->doctor_name ?? '',
                'doctorPhone' => $child->medicalInfo->doctor_phone ?? '',
                'insuranceInfo' => $child->medicalInfo->insurance_info ?? '',
                'emergencyInstructions' => $child->medicalInfo->emergency_instructions ?? '',
            ]);
        } else {
            $this->resetMedicalForm();
        }

        $this->showMedicalInfoModal = true;
    }

    public function saveMedicalInfo(): void
    {
        $this->authorize('update', $this->selectedChild);

        $data = [
            'allergies' => $this->allergies ?: null,
            'medical_conditions' => $this->medicalConditions ?: null,
            'medications' => $this->medications ?: null,
            'special_needs' => $this->specialNeeds ?: null,
            'dietary_restrictions' => $this->dietaryRestrictions ?: null,
            'blood_type' => $this->bloodType ?: null,
            'doctor_name' => $this->doctorName ?: null,
            'doctor_phone' => $this->doctorPhone ?: null,
            'insurance_info' => $this->insuranceInfo ?: null,
            'emergency_instructions' => $this->emergencyInstructions ?: null,
        ];

        ChildMedicalInfo::updateOrCreate(
            ['member_id' => $this->selectedChild->id],
            $data
        );

        unset($this->children);
        unset($this->stats);

        $this->showMedicalInfoModal = false;
        $this->selectedChild = null;
        $this->resetMedicalForm();
        $this->dispatch('medical-info-saved');
    }

    public function closeMedicalInfoModal(): void
    {
        $this->showMedicalInfoModal = false;
        $this->selectedChild = null;
        $this->resetMedicalForm();
    }

    protected function resetContactForm(): void
    {
        $this->reset([
            'contactName', 'contactRelationship', 'contactPhone', 'contactPhoneSecondary',
            'contactEmail', 'contactIsPrimary', 'contactCanPickup', 'contactNotes',
        ]);
        $this->contactCanPickup = true;
        $this->resetValidation();
    }

    protected function resetMedicalForm(): void
    {
        $this->reset([
            'allergies', 'medicalConditions', 'medications', 'specialNeeds',
            'dietaryRestrictions', 'bloodType', 'doctorName', 'doctorPhone',
            'insuranceInfo', 'emergencyInstructions',
        ]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.children.children-directory');
    }
}
