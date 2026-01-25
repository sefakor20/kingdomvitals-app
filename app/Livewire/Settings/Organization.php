<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\ImageProcessingService;
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

        // Delete existing logo if present
        if ($tenant->hasLogo()) {
            $imageService->deleteLogoByPaths($tenant->logo);
        }

        // Process and store the new logo (tenant-specific path)
        $paths = $imageService->processLogo($this->logo, "logos/tenants/{$tenant->id}");

        // Save paths to tenant
        $tenant->setLogoPaths($paths);

        // Update URL for display
        $this->existingLogoUrl = $tenant->getLogoUrl('medium');
        $this->logo = null;

        $this->dispatch('logo-saved');
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
