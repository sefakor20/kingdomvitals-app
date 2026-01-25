<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\ImageProcessingService;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
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

        // Delete existing logo if present (from central storage)
        if ($tenant->hasLogo()) {
            $this->deleteLogoFromCentralStorage($tenant->logo);
        }

        // Process and store the new logo in central storage (bypasses tenant storage isolation)
        $paths = $this->processLogoToCentralStorage($this->logo, $tenant->id);

        // Save paths to tenant
        $tenant->setLogoPaths($paths);

        // Update URL for display
        $this->existingLogoUrl = $tenant->getLogoUrl('medium');
        $this->logo = null;

        $this->dispatch('logo-saved');
    }

    /**
     * Process logo and store in central storage (bypasses tenant storage isolation).
     *
     * @return array<string, string>
     */
    private function processLogoToCentralStorage(TemporaryUploadedFile $file, string $tenantId): array
    {
        $paths = [];
        $sizes = ImageProcessingService::LOGO_SIZES;

        // Use base_path to store in central storage, bypassing tenant storage prefix
        $directory = base_path("storage/app/public/logos/tenants/{$tenantId}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($sizes as $sizeName => $targetSize) {
            $resized = Image::read($file->getRealPath());
            $resized->cover($targetSize, $targetSize);

            $filename = "logo-{$sizeName}.png";
            $fullPath = $directory.'/'.$filename;

            $encoded = $resized->encode(new PngEncoder);
            file_put_contents($fullPath, (string) $encoded);

            // Store relative path for URL generation
            $paths[$sizeName] = "logos/tenants/{$tenantId}/{$filename}";
        }

        return $paths;
    }

    /**
     * Delete logo files from central storage.
     *
     * @param  array<string, string>  $paths
     */
    private function deleteLogoFromCentralStorage(array $paths): void
    {
        foreach ($paths as $relativePath) {
            $fullPath = base_path('storage/app/public/'.$relativePath);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
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

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.settings.organization');
    }
}
