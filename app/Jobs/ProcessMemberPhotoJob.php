<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\Member;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessMemberPhotoJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param  string  $tenantId  The tenant ID
     * @param  string  $memberId  The member ID
     * @param  string  $tempFilePath  Absolute path to the staged temp file
     * @param  string  $processingToken  Unique token to handle superseded uploads
     * @param  string|null  $oldPhotoUrl  URL of old photo to delete after success
     */
    public function __construct(
        public string $tenantId,
        public string $memberId,
        public string $tempFilePath,
        public string $processingToken,
        public ?string $oldPhotoUrl = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageProcessingService $imageService, ImageStagingService $stagingService): void
    {
        // Check if this job has been superseded by a newer upload (use file store to bypass tenant cache tagging)
        $currentToken = cache()->store('file')->get("member:{$this->memberId}:photo_processing_token");
        if ($currentToken !== $this->processingToken) {
            Log::info('ProcessMemberPhotoJob: Superseded by newer upload', [
                'member_id' => $this->memberId,
            ]);
            $stagingService->cleanup($this->tempFilePath);

            return;
        }

        // Verify temp file exists
        if (! file_exists($this->tempFilePath)) {
            Log::error('ProcessMemberPhotoJob: Temp file not found', [
                'member_id' => $this->memberId,
                'temp_file' => $this->tempFilePath,
            ]);
            $this->clearProcessingState();

            return;
        }

        Log::info('ProcessMemberPhotoJob: Starting', [
            'member_id' => $this->memberId,
            'tenant_id' => $this->tenantId,
        ]);

        try {
            // Process the photo
            $photoUrl = $this->processAndStorePhoto($imageService);

            // Update member with new photo URL
            $member = Member::find($this->memberId);
            if ($member) {
                $member->update(['photo_url' => $photoUrl]);

                // Delete old photo if it exists
                if ($this->oldPhotoUrl) {
                    $this->deleteOldPhoto($this->oldPhotoUrl);
                }

                Log::info('ProcessMemberPhotoJob: Completed', [
                    'member_id' => $this->memberId,
                    'photo_url' => $photoUrl,
                ]);
            } else {
                Log::warning('ProcessMemberPhotoJob: Member not found', [
                    'member_id' => $this->memberId,
                ]);
            }

            // Clear processing state
            $this->clearProcessingState();

            // Cleanup staged file
            $stagingService->cleanup($this->tempFilePath);

        } catch (\Throwable $e) {
            Log::error('ProcessMemberPhotoJob: Error during processing', [
                'member_id' => $this->memberId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process the photo and store in central storage.
     */
    private function processAndStorePhoto(ImageProcessingService $imageService): string
    {
        $filename = Str::random(40).'.jpg';

        // Use base_path to store in central storage, bypassing tenant storage prefix
        $directory = base_path("storage/app/public/members/{$this->tenantId}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Process image (crop to square, resize to 256x256, convert to JPEG)
        $processed = $imageService->processMemberPhotoFromPath($this->tempFilePath);

        file_put_contents($directory.'/'.$filename, $processed);

        return "/storage/members/{$this->tenantId}/{$filename}";
    }

    /**
     * Delete the old photo from central storage.
     */
    private function deleteOldPhoto(string $photoUrl): void
    {
        // Extract relative path from URL (e.g., /storage/members/{tenant}/{file}.jpg)
        $relativePath = str_replace('/storage/', '', parse_url($photoUrl, PHP_URL_PATH));

        if ($relativePath) {
            $fullPath = base_path('storage/app/public/'.$relativePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Clear the processing state from cache.
     */
    private function clearProcessingState(): void
    {
        // Use file store to bypass tenant cache tagging
        $fileCache = cache()->store('file');
        $fileCache->forget("member:{$this->memberId}:photo_processing");
        $fileCache->forget("member:{$this->memberId}:photo_processing_token");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessMemberPhotoJob failed', [
            'member_id' => $this->memberId,
            'tenant_id' => $this->tenantId,
            'exception' => $exception->getMessage(),
        ]);

        // Clean up staged file if it exists
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }

        // Clear processing state and store error for user notification (use file store to bypass tenant cache tagging)
        $fileCache = cache()->store('file');
        $fileCache->forget("member:{$this->memberId}:photo_processing");
        $fileCache->forget("member:{$this->memberId}:photo_processing_token");
        $fileCache->put(
            "member:{$this->memberId}:photo_error",
            __('Failed to process photo. Please try again.'),
            now()->addHour()
        );
    }
}
