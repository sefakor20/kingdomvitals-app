<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;

class ProcessLogoJob implements ShouldQueue
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
     * @param  string  $tenantId  The tenant ID
     * @param  string  $tempFilePath  Absolute path to the staged temp file
     * @param  string  $processingToken  Unique token to handle superseded uploads
     * @param  array<string, string>|null  $existingLogoPaths  Paths to delete after successful processing
     */
    public function __construct(
        public string $tenantId,
        public string $tempFilePath,
        public string $processingToken,
        public ?array $existingLogoPaths = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageStagingService $stagingService): void
    {
        // Check if this job has been superseded by a newer upload (use file store to bypass tenant cache tagging)
        $currentToken = cache()->store('file')->get("tenant:{$this->tenantId}:logo_processing_token");
        if ($currentToken !== $this->processingToken) {
            Log::info('ProcessLogoJob: Superseded by newer upload', [
                'tenant_id' => $this->tenantId,
            ]);
            $stagingService->cleanup($this->tempFilePath);

            return;
        }

        // Verify temp file exists
        if (! file_exists($this->tempFilePath)) {
            Log::error('ProcessLogoJob: Temp file not found', [
                'tenant_id' => $this->tenantId,
                'temp_file' => $this->tempFilePath,
            ]);
            $this->clearProcessingState();

            return;
        }

        Log::info('ProcessLogoJob: Starting', [
            'tenant_id' => $this->tenantId,
        ]);

        try {
            // Process all logo sizes
            $paths = $this->processLogoSizes();

            // Update tenant with new logo paths
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenant->setLogoPaths($paths);

                // Delete old logo files if they exist
                if ($this->existingLogoPaths) {
                    $this->deleteOldLogoFiles($this->existingLogoPaths);
                }

                Log::info('ProcessLogoJob: Completed', [
                    'tenant_id' => $this->tenantId,
                    'paths' => $paths,
                ]);
            } else {
                Log::warning('ProcessLogoJob: Tenant not found', [
                    'tenant_id' => $this->tenantId,
                ]);
            }

            // Clear processing state
            $this->clearProcessingState();

            // Cleanup staged file
            $stagingService->cleanup($this->tempFilePath);

        } catch (\Throwable $e) {
            Log::error('ProcessLogoJob: Error during processing', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process all logo size variants.
     *
     * @return array<string, string>
     */
    private function processLogoSizes(): array
    {
        $paths = [];
        $sizes = ImageProcessingService::LOGO_SIZES;

        // Use base_path to store in central storage, bypassing tenant storage prefix
        $directory = base_path("storage/app/public/logos/tenants/{$this->tenantId}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($sizes as $sizeName => $targetSize) {
            $resized = Image::read($this->tempFilePath);
            $resized->cover($targetSize, $targetSize);

            $filename = "logo-{$sizeName}.png";
            $fullPath = $directory.'/'.$filename;

            $encoded = $resized->encode(new PngEncoder);
            file_put_contents($fullPath, (string) $encoded);

            // Store relative path for database
            $paths[$sizeName] = "logos/tenants/{$this->tenantId}/{$filename}";
        }

        return $paths;
    }

    /**
     * Delete old logo files from central storage.
     *
     * @param  array<string, string>  $logoPaths
     */
    private function deleteOldLogoFiles(array $logoPaths): void
    {
        foreach ($logoPaths as $sizeName => $relativePath) {
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
        $fileCache->forget("tenant:{$this->tenantId}:logo_processing");
        $fileCache->forget("tenant:{$this->tenantId}:logo_processing_token");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLogoJob failed', [
            'tenant_id' => $this->tenantId,
            'exception' => $exception->getMessage(),
        ]);

        // Clean up staged file if it exists
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }

        // Clear processing state and store error for user notification (use file store to bypass tenant cache tagging)
        $fileCache = cache()->store('file');
        $fileCache->forget("tenant:{$this->tenantId}:logo_processing");
        $fileCache->forget("tenant:{$this->tenantId}:logo_processing_token");
        $fileCache->put(
            "tenant:{$this->tenantId}:logo_error",
            __('Failed to process logo. Please try again.'),
            now()->addHour()
        );
    }
}
