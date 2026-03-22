<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\Currency;
use App\Jobs\ProcessLogoJob;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Organization extends Component
{
    use WithFileUploads;

    // Organization Logo
    public TemporaryUploadedFile|string|null $logo = null;

    public ?string $existingLogoUrl = null;

    public bool $isProcessingLogo = false;

    // Organization Currency
    public string $currency = 'GHS';

    public function mount(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        // Load existing logo
        if ($tenant->hasLogo()) {
            $this->existingLogoUrl = $tenant->getLogoUrl('medium');
        }

        // Check if logo is currently being processed (use file store to bypass tenant cache tagging)
        $this->isProcessingLogo = cache()->store('file')->get("tenant:{$tenant->id}:logo_processing", false);

        // Load existing currency
        $this->currency = $tenant->getCurrencyCode();
    }

    public function saveLogo(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        if (! $this->logo instanceof TemporaryUploadedFile) {
            return;
        }

        $imageService = app(ImageProcessingService::class);

        // Validate the logo
        $errors = $imageService->validateLogo($this->logo);
        if (! empty($errors)) {
            foreach ($errors as $message) {
                $this->addError('logo', $message);
            }

            return;
        }

        // Stage the file for background processing
        $stagingService = app(ImageStagingService::class);
        $stagedPath = $stagingService->stageForProcessing($this->logo, 'logo');

        // Generate a unique token to handle re-uploads
        $processingToken = Str::uuid()->toString();

        // Set processing state in cache (use file store to bypass tenant cache tagging)
        cache()->store('file')->put("tenant:{$tenant->id}:logo_processing", true, now()->addMinutes(5));
        cache()->store('file')->put("tenant:{$tenant->id}:logo_processing_token", $processingToken, now()->addMinutes(5));

        // Get existing logo paths for deletion after successful processing
        $existingLogoPaths = $tenant->hasLogo() ? $tenant->logo : null;

        // Dispatch the job
        ProcessLogoJob::dispatch(
            tenantId: $tenant->id,
            tempFilePath: $stagedPath,
            processingToken: $processingToken,
            existingLogoPaths: $existingLogoPaths,
        );

        $this->logo = null;
        $this->isProcessingLogo = true;

        $this->dispatch('logo-processing-started');
    }

    /**
     * Check logo processing status (called by wire:poll).
     */
    public function checkLogoStatus(): void
    {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        // Check for error (use file store to bypass tenant cache tagging)
        $fileCache = cache()->store('file');
        $error = $fileCache->get("tenant:{$tenant->id}:logo_error");
        $fileCache->forget("tenant:{$tenant->id}:logo_error");
        if ($error) {
            $this->isProcessingLogo = false;
            $this->addError('logo', $error);

            return;
        }

        // Check if still processing
        if (! $fileCache->get("tenant:{$tenant->id}:logo_processing")) {
            $this->isProcessingLogo = false;
            $tenant->refresh();
            $this->existingLogoUrl = $tenant->getLogoUrl('medium');
            $this->dispatch('logo-saved');
        }
    }

    /**
     * Delete logo files from central storage.
     *
     * @param  array<string, string>  $paths
     */
    private function deleteLogoFromCentralStorage(array $paths): void
    {
        foreach ($paths as $sizeName => $relativePath) {
            $fullPath = base_path('storage/app/public/'.$relativePath);
            if (file_exists($fullPath) && ! unlink($fullPath)) {
                Log::warning('Organization: Failed to delete logo file', [
                    'tenant_id' => tenant()?->id,
                    'size' => $sizeName,
                    'path' => $relativePath,
                ]);
            }
        }
    }

    public function removeLogo(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        $tenant->clearLogo();
        $this->existingLogoUrl = null;
        $this->logo = null;

        $this->dispatch('logo-removed');
    }

    public function saveCurrency(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        $currency = Currency::fromString($this->currency);
        $tenant->setCurrency($currency);

        $this->dispatch('currency-saved');
    }

    public function render(): Factory|View
    {
        return view('livewire.settings.organization', [
            'currencies' => Currency::options(),
        ]);
    }
}
