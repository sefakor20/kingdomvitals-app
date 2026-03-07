<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SystemSetting;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPlatformLogoJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param  string  $tempFilePath  Absolute path to the staged temp file
     * @param  string  $processingToken  Unique token to handle superseded uploads
     * @param  array<string, string>|null  $existingLogoPaths  Paths to delete after successful processing
     */
    public function __construct(
        public string $tempFilePath,
        public string $processingToken,
        public ?array $existingLogoPaths = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageProcessingService $imageService, ImageStagingService $stagingService): void
    {
        // Check if this job has been superseded by a newer upload (use file store to bypass tenant cache tagging)
        $currentToken = cache()->store('file')->get('platform:logo_processing_token');
        if ($currentToken !== $this->processingToken) {
            Log::info('ProcessPlatformLogoJob: Superseded by newer upload');
            $stagingService->cleanup($this->tempFilePath);

            return;
        }

        // Verify temp file exists
        if (! file_exists($this->tempFilePath)) {
            Log::error('ProcessPlatformLogoJob: Temp file not found', [
                'temp_file' => $this->tempFilePath,
            ]);
            $this->clearProcessingState();

            return;
        }

        Log::info('ProcessPlatformLogoJob: Starting');

        try {
            // Create a temporary uploaded file from the staged file
            $paths = $imageService->processLogoFromPath($this->tempFilePath, 'logos/platform');

            // Save paths to system settings
            SystemSetting::set('platform_logo', $paths, 'app');

            // Delete old logo files if they exist
            if ($this->existingLogoPaths) {
                $imageService->deleteLogoByPaths($this->existingLogoPaths);
            }

            Log::info('ProcessPlatformLogoJob: Completed', [
                'paths' => $paths,
            ]);

            // Clear processing state
            $this->clearProcessingState();

            // Cleanup staged file
            $stagingService->cleanup($this->tempFilePath);

        } catch (\Throwable $e) {
            Log::error('ProcessPlatformLogoJob: Error during processing', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clear the processing state from cache.
     */
    private function clearProcessingState(): void
    {
        // Use file store to bypass tenant cache tagging
        $fileCache = cache()->store('file');
        $fileCache->forget('platform:logo_processing');
        $fileCache->forget('platform:logo_processing_token');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPlatformLogoJob failed', [
            'exception' => $exception->getMessage(),
        ]);

        // Clean up staged file if it exists
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }

        // Clear processing state and store error for user notification (use file store to bypass tenant cache tagging)
        $fileCache = cache()->store('file');
        $fileCache->forget('platform:logo_processing');
        $fileCache->forget('platform:logo_processing_token');
        $fileCache->put(
            'platform:logo_error',
            __('Failed to process logo. Please try again.'),
            now()->addHour()
        );
    }
}
