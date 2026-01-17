<?php

namespace App\Livewire\Members;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Enums\QuotaType;
use App\Livewire\Concerns\HasFilterableQuery;
use App\Livewire\Concerns\HasQuotaComputed;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\PlanAccessService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class MemberIndex extends Component
{
    use HasFilterableQuery;
    use HasQuotaComputed;
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

    public string $maiden_name = '';

    public string $profession = '';

    public string $employment_status = '';

    public string $hometown = '';

    public string $gps_address = '';

    public string $previous_congregation = '';

    public ?string $confirmation_date = null;

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

        $this->applySearch($query, ['first_name', 'last_name', 'email', 'phone']);
        $this->applyEnumFilter($query, 'statusFilter', 'status');
        $this->applyBooleanFilter($query, 'smsOptOutFilter', 'sms_opt_out', 'opted_out');

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
    public function employmentStatuses(): array
    {
        return EmploymentStatus::cases();
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

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return $this->showQuotaWarningFor(QuotaType::Members);
    }

    /**
     * Check if member creation is allowed based on quota.
     */
    #[Computed]
    public function canCreateWithinQuota(): bool
    {
        return $this->canCreateWithinQuotaFor(QuotaType::Members);
    }

    /**
     * Check if the storage quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showStorageWarning(): bool
    {
        return $this->showQuotaWarningFor(QuotaType::Storage);
    }

    /**
     * Check if member import feature is available on the current plan.
     * This is a placeholder for future member import functionality.
     */
    #[Computed]
    public function canImportMembers(): bool
    {
        return app(PlanAccessService::class)->hasFeature('member_import');
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'maiden_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'marital_status' => ['nullable', 'string', 'in:single,married,divorced,widowed'],
            'profession' => ['nullable', 'string', 'max:100'],
            'employment_status' => ['nullable', 'string', 'in:employed,self_employed,unemployed,student,retired'],
            'status' => ['required', 'string', 'in:active,inactive,pending,deceased,transferred'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'hometown' => ['nullable', 'string', 'max:100'],
            'gps_address' => ['nullable', 'string', 'max:100'],
            'joined_at' => ['nullable', 'date'],
            'baptized_at' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'previous_congregation' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);

        // Double-check quota (UI should already prevent this, but be safe)
        if (! app(PlanAccessService::class)->canCreateMember()) {
            $this->dispatch('quota-exceeded');

            return;
        }

        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function store(): void
    {
        $this->authorize('create', [Member::class, $this->branch]);

        // Check member quota before creation
        if (! app(PlanAccessService::class)->canCreateMember()) {
            $this->addError('first_name', __('Member quota exceeded for your plan. Please upgrade to add more members.'));

            return;
        }

        $validated = $this->validate();

        $validated['primary_branch_id'] = $this->branch->id;

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'middle_name', 'maiden_name', 'email', 'phone', 'gender', 'marital_status',
            'profession', 'employment_status', 'address', 'city', 'state', 'zip', 'country',
            'hometown', 'gps_address', 'date_of_birth', 'joined_at', 'baptized_at',
            'confirmation_date', 'notes', 'previous_congregation',
        ];
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        // Handle photo upload - store in central public storage
        if ($this->photo instanceof TemporaryUploadedFile) {
            // Check storage quota before uploading
            if (! app(PlanAccessService::class)->canUploadFile($this->photo->getSize())) {
                $this->addError('photo', __('Storage quota exceeded. Please delete some files or upgrade your plan.'));

                return;
            }
            $validated['photo_url'] = $this->storePhotoInCentralStorage($this->photo);
            // Invalidate storage cache after upload
            app(PlanAccessService::class)->invalidateCountCache('storage');
        }

        // Remove photo from validated data (it's not a model field)
        unset($validated['photo']);

        Member::create($validated);

        // Invalidate member count cache for quota tracking
        app(PlanAccessService::class)->invalidateCountCache('members');

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
            'maiden_name' => $member->maiden_name ?? '',
            'email' => $member->email ?? '',
            'phone' => $member->phone ?? '',
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'gender' => $member->gender?->value ?? '',
            'marital_status' => $member->marital_status?->value ?? '',
            'profession' => $member->profession ?? '',
            'employment_status' => $member->employment_status?->value ?? '',
            'status' => $member->status->value,
            'address' => $member->address ?? '',
            'city' => $member->city ?? '',
            'state' => $member->state ?? '',
            'zip' => $member->zip ?? '',
            'country' => $member->country ?? 'Ghana',
            'hometown' => $member->hometown ?? '',
            'gps_address' => $member->gps_address ?? '',
            'joined_at' => $member->joined_at?->format('Y-m-d'),
            'baptized_at' => $member->baptized_at?->format('Y-m-d'),
            'confirmation_date' => $member->confirmation_date?->format('Y-m-d'),
            'notes' => $member->notes ?? '',
            'previous_congregation' => $member->previous_congregation ?? '',
        ]);
        $this->showEditModal = true;
    }

    public function update(): void
    {
        $this->authorize('update', $this->editingMember);
        $validated = $this->validate();

        // Handle photo upload - store in central public storage
        if ($this->photo instanceof TemporaryUploadedFile) {
            // Check storage quota before uploading
            if (! app(PlanAccessService::class)->canUploadFile($this->photo->getSize())) {
                $this->addError('photo', __('Storage quota exceeded. Please delete some files or upgrade your plan.'));

                return;
            }

            // Delete old photo if exists
            $this->deleteOldPhoto($this->editingMember);

            $validated['photo_url'] = $this->storePhotoInCentralStorage($this->photo);
            // Invalidate storage cache after upload
            app(PlanAccessService::class)->invalidateCountCache('storage');
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

        // Invalidate member count cache for quota tracking
        app(PlanAccessService::class)->invalidateCountCache('members');

        $this->showDeleteModal = false;
        $this->deletingMember = null;
        $this->dispatch('member-deleted');
    }

    public function restore(string $memberId): void
    {
        $member = Member::onlyTrashed()->where('id', $memberId)->firstOrFail();
        $this->authorize('restore', $member);
        $member->restore();

        // Invalidate member count cache for quota tracking
        app(PlanAccessService::class)->invalidateCountCache('members');

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
        if ($this->editingMember instanceof \App\Models\Tenant\Member) {
            $this->authorize('update', $this->editingMember);
            $this->deleteOldPhoto($this->editingMember);
            $this->editingMember->update(['photo_url' => null]);
            $this->existingPhotoUrl = null;
            // Invalidate storage cache after deletion
            app(PlanAccessService::class)->invalidateCountCache('storage');
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
            'first_name', 'last_name', 'middle_name', 'maiden_name', 'email', 'phone',
            'date_of_birth', 'gender', 'marital_status', 'profession', 'employment_status',
            'address', 'city', 'state', 'zip', 'hometown', 'gps_address',
            'joined_at', 'baptized_at', 'confirmation_date', 'notes', 'previous_congregation',
            'photo', 'existingPhotoUrl',
        ]);
        $this->status = 'active';
        $this->country = 'Ghana';
        $this->resetValidation();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.members.member-index');
    }
}
