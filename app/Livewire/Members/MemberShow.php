<?php

namespace App\Livewire\Members;

use App\Enums\ClusterRole;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MembershipStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class MemberShow extends Component
{
    use WithFileUploads;

    public Branch $branch;

    public Member $member;

    public bool $editing = false;

    // Photo fields
    public TemporaryUploadedFile|string|null $photo = null;

    public ?string $existingPhotoUrl = null;

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

    // Cluster management properties
    public bool $showAddClusterModal = false;

    public string $selectedClusterId = '';

    public string $selectedClusterRole = 'member';

    public ?string $clusterJoinedAt = null;

    // Delete modal
    public bool $showDeleteModal = false;

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

    #[Computed]
    public function memberClusters(): Collection
    {
        return $this->member->clusters()
            ->where('branch_id', $this->branch->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableClusters(): Collection
    {
        $existingClusterIds = $this->member->clusters()->pluck('clusters.id')->toArray();

        return Cluster::query()
            ->where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->whereNotIn('id', $existingClusterIds)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function clusterRoles(): array
    {
        return ClusterRole::cases();
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

    public function edit(): void
    {
        $this->authorize('update', $this->member);

        $this->existingPhotoUrl = $this->member->photo_url;
        $this->photo = null;

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

        // Handle photo upload
        if ($this->photo instanceof TemporaryUploadedFile) {
            // Delete old photo if exists
            $this->deleteOldPhoto($this->member);
            $validated['photo_url'] = $this->storePhotoInCentralStorage($this->photo);
        }

        // Remove photo from validated data (it's not a model field)
        unset($validated['photo']);

        $this->member->update($validated);
        $this->member->refresh();

        $this->editing = false;
        $this->existingPhotoUrl = null;
        $this->photo = null;
        $this->dispatch('member-updated');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->photo = null;
        $this->existingPhotoUrl = null;
        $this->resetValidation();
    }

    public function removePhoto(): void
    {
        $this->authorize('update', $this->member);

        if ($this->existingPhotoUrl) {
            $this->deleteOldPhoto($this->member);
            $this->member->update(['photo_url' => null]);
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

    public function openAddClusterModal(): void
    {
        $this->authorize('update', $this->member);

        $this->reset(['selectedClusterId', 'selectedClusterRole', 'clusterJoinedAt']);
        $this->selectedClusterRole = ClusterRole::Member->value;
        $this->clusterJoinedAt = now()->format('Y-m-d');
        $this->showAddClusterModal = true;
    }

    public function closeAddClusterModal(): void
    {
        $this->showAddClusterModal = false;
        $this->reset(['selectedClusterId', 'selectedClusterRole', 'clusterJoinedAt']);
    }

    public function addToCluster(): void
    {
        $this->authorize('update', $this->member);

        $this->validate([
            'selectedClusterId' => ['required', 'uuid', 'exists:clusters,id'],
            'selectedClusterRole' => ['required', 'string', 'in:leader,assistant,member'],
            'clusterJoinedAt' => ['nullable', 'date'],
        ]);

        // Verify cluster belongs to same branch and is active
        $cluster = Cluster::where('id', $this->selectedClusterId)
            ->where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->first();

        if (! $cluster) {
            $this->addError('selectedClusterId', __('Invalid cluster selected.'));

            return;
        }

        // Check if already a member
        if ($this->member->clusters()->where('cluster_id', $cluster->id)->exists()) {
            $this->addError('selectedClusterId', __('Member is already in this cluster.'));

            return;
        }

        $this->member->clusters()->attach($cluster->id, [
            'role' => $this->selectedClusterRole,
            'joined_at' => $this->clusterJoinedAt,
        ]);

        $this->member->refresh();
        $this->closeAddClusterModal();
        $this->dispatch('cluster-added');
    }

    public function removeFromCluster(string $clusterId): void
    {
        $this->authorize('update', $this->member);

        $this->member->clusters()->detach($clusterId);
        $this->member->refresh();
        $this->dispatch('cluster-removed');
    }

    public function updateClusterRole(string $clusterId, string $newRole): void
    {
        $this->authorize('update', $this->member);

        if (! in_array($newRole, ['leader', 'assistant', 'member'])) {
            return;
        }

        $this->member->clusters()->updateExistingPivot($clusterId, [
            'role' => $newRole,
        ]);

        $this->member->refresh();
        $this->dispatch('cluster-updated');
    }

    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->member);
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->member);
        $this->member->delete();
        $this->dispatch('member-deleted');
        $this->redirect(route('members.index', $this->branch), navigate: true);
    }

    public function render()
    {
        return view('livewire.members.member-show');
    }
}
