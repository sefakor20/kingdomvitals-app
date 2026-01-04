<?php

namespace App\Livewire\Members;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class MemberIndex extends Component
{
    use WithFileUploads;

    public Branch $branch;

    public string $search = '';

    public string $statusFilter = '';

    public string $smsOptOutFilter = '';

    public string $viewFilter = 'active';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    // Form properties
    public string $first_name = '';

    public TemporaryUploadedFile|string|null $photo = null;

    public ?string $existingPhotoUrl = null;

    public string $last_name = '';

    public string $middle_name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $date_of_birth = null;

    public string $gender = '';

    public string $marital_status = '';

    public string $status = 'active';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $country = 'Ghana';

    public ?string $joined_at = null;

    public ?string $baptized_at = null;

    public string $notes = '';

    public ?Member $editingMember = null;

    public ?Member $deletingMember = null;

    public ?Member $forceDeleting = null;

    public bool $showForceDeleteModal = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Member::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function members(): Collection
    {
        $query = Member::where('primary_branch_id', $this->branch->id);

        if ($this->viewFilter === 'deleted') {
            $query->onlyTrashed();
        }

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

        if ($this->smsOptOutFilter !== '') {
            $query->where('sms_opt_out', $this->smsOptOutFilter === 'opted_out');
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    #[Computed]
    public function statuses(): array
    {
        return MembershipStatus::cases();
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
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Member::class, $this->branch]);
    }

    #[Computed]
    public function canRestore(): bool
    {
        return auth()->user()->can('deleteAny', [Member::class, $this->branch]);
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
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);
        $validated = $this->validate();

        $validated['primary_branch_id'] = $this->branch->id;

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

        // Handle photo upload - store in central public storage
        if ($this->photo instanceof TemporaryUploadedFile) {
            $validated['photo_url'] = $this->storePhotoInCentralStorage($this->photo);
        }

        // Remove photo from validated data (it's not a model field)
        unset($validated['photo']);

        Member::create($validated);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('member-created');
    }

    public function edit(Member $member): void
    {
        $this->authorize('update', $member);
        $this->editingMember = $member;
        $this->existingPhotoUrl = $member->photo_url;
        $this->photo = null;
        $this->fill([
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'middle_name' => $member->middle_name ?? '',
            'email' => $member->email ?? '',
            'phone' => $member->phone ?? '',
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'gender' => $member->gender?->value ?? '',
            'marital_status' => $member->marital_status?->value ?? '',
            'status' => $member->status->value,
            'address' => $member->address ?? '',
            'city' => $member->city ?? '',
            'state' => $member->state ?? '',
            'zip' => $member->zip ?? '',
            'country' => $member->country ?? 'Ghana',
            'joined_at' => $member->joined_at?->format('Y-m-d'),
            'baptized_at' => $member->baptized_at?->format('Y-m-d'),
            'notes' => $member->notes ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingMember);
        $validated = $this->validate();

        // Handle photo upload - store in central public storage
        if ($this->photo instanceof TemporaryUploadedFile) {
            // Delete old photo if exists
            $this->deleteOldPhoto($this->editingMember);

            $validated['photo_url'] = $this->storePhotoInCentralStorage($this->photo);
        }

        // Remove photo from validated data (it's not a model field)
        unset($validated['photo']);

        $this->editingMember->update($validated);

        $this->showEditModal = false;
        $this->editingMember = null;
        $this->resetForm();
        $this->dispatch('member-updated');
    }

    public function confirmDelete(Member $member): void
    {
        $this->authorize('delete', $member);
        $this->deletingMember = $member;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->deletingMember);

        $this->deletingMember->delete();
        $this->showDeleteModal = false;
        $this->deletingMember = null;
        $this->dispatch('member-deleted');
    }

    public function restore(string $memberId): void
    {
        $member = Member::onlyTrashed()->where('id', $memberId)->firstOrFail();
        $this->authorize('restore', $member);
        $member->restore();
        $this->dispatch('member-restored');
    }

    public function confirmForceDelete(string $memberId): void
    {
        $member = Member::onlyTrashed()->where('id', $memberId)->firstOrFail();
        $this->authorize('forceDelete', $member);
        $this->forceDeleting = $member;
        $this->showForceDeleteModal = true;
    }

    public function cancelForceDelete(): void
    {
        $this->showForceDeleteModal = false;
        $this->forceDeleting = null;
    }

    public function forceDelete(): void
    {
        $this->authorize('forceDelete', $this->forceDeleting);
        $this->forceDeleting->forceDelete();
        $this->showForceDeleteModal = false;
        $this->forceDeleting = null;
        $this->dispatch('member-force-deleted');
    }

    public function cancelCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->editingMember = null;
        $this->resetForm();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingMember = null;
    }

    public function removePhoto(): void
    {
        if ($this->editingMember) {
            $this->authorize('update', $this->editingMember);
            $this->deleteOldPhoto($this->editingMember);
            $this->editingMember->update(['photo_url' => null]);
            $this->existingPhotoUrl = null;
        }
        $this->photo = null;
    }

    private function deleteOldPhoto(Member $member): void
    {
        if ($member->photo_url) {
            // Extract path from URL and delete from central storage
            $relativePath = str_replace('/storage/', '', parse_url($member->photo_url, PHP_URL_PATH));
            // Use base_path to avoid tenant storage path prefix
            $fullPath = base_path('storage/app/public/'.$relativePath);

            if ($relativePath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    private function storePhotoInCentralStorage(TemporaryUploadedFile $photo): string
    {
        $tenantId = tenant()->id;
        $filename = $photo->hashName();

        // Use base_path to avoid tenant storage path prefix
        $directory = base_path("storage/app/public/members/{$tenantId}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $destination = $directory.'/'.$filename;

        // Use copy + unlink instead of move to handle cross-filesystem transfers
        // (tenant storage to central storage)
        copy($photo->getRealPath(), $destination);
        @unlink($photo->getRealPath());

        return "/storage/members/{$tenantId}/{$filename}";
    }

    private function resetForm(): void
    {
        $this->reset([
            'first_name', 'last_name', 'middle_name', 'email', 'phone',
            'date_of_birth', 'gender', 'marital_status', 'address',
            'city', 'state', 'zip', 'joined_at', 'baptized_at', 'notes',
            'photo', 'existingPhotoUrl',
        ]);
        $this->status = 'active';
        $this->country = 'Ghana';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.members.member-index');
    }
}
