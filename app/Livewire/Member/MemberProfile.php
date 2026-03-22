<?php

declare(strict_types=1);

namespace App\Livewire\Member;

use App\Jobs\ProcessMemberPhotoJob;
use App\Models\Tenant\Member;
use App\Services\ImageStagingService;
use App\Services\PlanAccessService;
use Flux\Flux;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.member')]
class MemberProfile extends Component
{
    use WithFileUploads;

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:100')]
    public string $city = '';

    #[Validate('nullable|string|max:100')]
    public string $state = '';

    #[Validate('nullable|string|max:20')]
    public string $zip = '';

    #[Validate('nullable|string|max:100')]
    public string $profession = '';

    // Photo properties
    public TemporaryUploadedFile|string|null $photo = null;

    public bool $isProcessingPhoto = false;

    public function mount(): void
    {
        $member = $this->member;

        $this->phone = $member->phone ?? '';
        $this->address = $member->address ?? '';
        $this->city = $member->city ?? '';
        $this->state = $member->state ?? '';
        $this->zip = $member->zip ?? '';
        $this->profession = $member->profession ?? '';
    }

    #[Computed]
    public function member(): Member
    {
        return Member::where('user_id', auth()->id())
            ->whereNotNull('portal_activated_at')
            ->firstOrFail();
    }

    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    public function uploadPhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        if (! $this->photo instanceof TemporaryUploadedFile) {
            return;
        }

        // Check storage quota
        if (! app(PlanAccessService::class)->canUploadFile($this->photo->getSize())) {
            $this->addError('photo', __('Storage quota exceeded. Please contact your church administrator.'));

            return;
        }

        $this->dispatchPhotoProcessingJob();

        // Invalidate storage cache
        app(PlanAccessService::class)->invalidateCountCache('storage');

        Flux::toast(__('Photo is being processed...'));
    }

    public function removePhoto(): void
    {
        if ($this->member->photo_url) {
            $this->deleteOldPhoto();
            $this->member->update(['photo_url' => null]);

            // Invalidate storage cache
            app(PlanAccessService::class)->invalidateCountCache('storage');

            Flux::toast(__('Photo removed successfully.'));
        }

        $this->photo = null;
    }

    private function deleteOldPhoto(): void
    {
        $photoUrl = $this->member->photo_url;

        if ($photoUrl) {
            $relativePath = str_replace('/storage/', '', parse_url($photoUrl, PHP_URL_PATH));
            $fullPath = base_path('storage/app/public/'.$relativePath);

            if ($relativePath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    private function dispatchPhotoProcessingJob(): void
    {
        $tenant = tenant();
        if (! $tenant || ! $this->photo instanceof TemporaryUploadedFile) {
            return;
        }

        // Stage the file for background processing
        $stagingService = app(ImageStagingService::class);
        $stagedPath = $stagingService->stageForProcessing($this->photo, 'member-photo');

        // Generate a unique token to handle re-uploads
        $processingToken = Str::uuid()->toString();

        // Set processing state in cache
        cache()->store('file')->put("member:{$this->member->id}:photo_processing", true, now()->addMinutes(5));
        cache()->store('file')->put("member:{$this->member->id}:photo_processing_token", $processingToken, now()->addMinutes(5));

        // Get old photo URL for deletion after successful processing
        $oldPhotoUrl = $this->member->photo_url;

        // Dispatch the job
        ProcessMemberPhotoJob::dispatch(
            tenantId: $tenant->id,
            memberId: $this->member->id,
            tempFilePath: $stagedPath,
            processingToken: $processingToken,
            oldPhotoUrl: $oldPhotoUrl,
        );

        $this->isProcessingPhoto = true;
        $this->photo = null;
    }

    public function checkPhotoStatus(): void
    {
        $fileCache = cache()->store('file');

        // Check for error
        $error = $fileCache->get("member:{$this->member->id}:photo_error");
        if ($error) {
            $fileCache->forget("member:{$this->member->id}:photo_error");
            $this->isProcessingPhoto = false;
            $this->addError('photo', $error);

            return;
        }

        // Check if still processing
        if (! $fileCache->get("member:{$this->member->id}:photo_processing")) {
            $this->isProcessingPhoto = false;
            $this->member->refresh();
            Flux::toast(__('Photo updated successfully.'));
        }
    }

    public function save(): void
    {
        $this->validate();

        $this->member->update([
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'zip' => $this->zip ?: null,
            'profession' => $this->profession ?: null,
        ]);

        Flux::toast(__('Profile updated successfully.'));
    }

    public function render(): Factory|View
    {
        return view('livewire.member.member-profile');
    }
}
