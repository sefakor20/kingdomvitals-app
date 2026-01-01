<?php

namespace App\Livewire\Members;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MemberShow extends Component
{
    public Branch $branch;

    public Member $member;

    public bool $editing = false;

    // Form fields
    public string $first_name = '';

    public string $last_name = '';

    public string $middle_name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $date_of_birth = null;

    public string $gender = '';

    public string $marital_status = '';

    public string $status = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $country = '';

    public ?string $joined_at = null;

    public ?string $baptized_at = null;

    public string $notes = '';

    public function mount(Branch $branch, Member $member): void
    {
        $this->authorize('view', $member);
        $this->branch = $branch;
        $this->member = $member;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->member);
    }

    #[Computed]
    public function canDelete(): bool
    {
        return auth()->user()->can('delete', $this->member);
    }

    #[Computed]
    public function genders(): array
    {
        return Gender::cases();
    }

    #[Computed]
    public function maritalStatuses(): array
    {
        return MaritalStatus::cases();
    }

    #[Computed]
    public function statuses(): array
    {
        return MembershipStatus::cases();
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'marital_status' => ['nullable', 'string', 'in:single,married,divorced,widowed'],
            'status' => ['required', 'string', 'in:active,inactive,pending,deceased,transferred'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'joined_at' => ['nullable', 'date'],
            'baptized_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function edit(): void
    {
        $this->authorize('update', $this->member);

        $this->fill([
            'first_name' => $this->member->first_name,
            'last_name' => $this->member->last_name,
            'middle_name' => $this->member->middle_name ?? '',
            'email' => $this->member->email ?? '',
            'phone' => $this->member->phone ?? '',
            'date_of_birth' => $this->member->date_of_birth?->format('Y-m-d'),
            'gender' => $this->member->gender?->value ?? '',
            'marital_status' => $this->member->marital_status?->value ?? '',
            'status' => $this->member->status->value,
            'address' => $this->member->address ?? '',
            'city' => $this->member->city ?? '',
            'state' => $this->member->state ?? '',
            'zip' => $this->member->zip ?? '',
            'country' => $this->member->country ?? '',
            'joined_at' => $this->member->joined_at?->format('Y-m-d'),
            'baptized_at' => $this->member->baptized_at?->format('Y-m-d'),
            'notes' => $this->member->notes ?? '',
        ]);

        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->member);
        $validated = $this->validate();

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'middle_name', 'email', 'phone', 'gender', 'marital_status',
            'address', 'city', 'state', 'zip', 'country',
            'date_of_birth', 'joined_at', 'baptized_at', 'notes',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        $this->member->update($validated);
        $this->member->refresh();

        $this->editing = false;
        $this->dispatch('member-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.members.member-show');
    }
}
