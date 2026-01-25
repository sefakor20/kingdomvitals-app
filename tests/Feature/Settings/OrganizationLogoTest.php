<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Services\ImageProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);
});

afterEach(function (): void {
    // Clean up uploaded files
    $logoDir = base_path("storage/app/public/logos/tenants/{$this->tenant->id}");
    if (is_dir($logoDir)) {
        array_map('unlink', glob("$logoDir/*.*") ?: []);
        @rmdir($logoDir);
    }

    tenancy()->end();
    $this->tenant?->delete();
});

// ============================================
// HELPER FUNCTION
// ============================================

function uploadLogoForTenant(Tenant $tenant): array
{
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = [];
    $sizes = ImageProcessingService::LOGO_SIZES;
    $directory = base_path("storage/app/public/logos/tenants/{$tenant->id}");

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

        $paths[$sizeName] = "logos/tenants/{$tenant->id}/{$filename}";
    }

    $tenant->setLogoPaths($paths);

    return $paths;
}

// ============================================
// LOGO UPLOAD TESTS
// ============================================

test('tenant can have logo set', function (): void {
    $paths = uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();

    expect($this->tenant->hasLogo())->toBeTrue()
        ->and($this->tenant->logo)->toBeArray()
        ->and($this->tenant->logo)->toHaveCount(5);
});

test('logo is processed into multiple sizes', function (): void {
    $paths = uploadLogoForTenant($this->tenant);

    expect($paths)->toBeArray()
        ->and($paths)->toHaveKey('favicon')
        ->and($paths)->toHaveKey('small')
        ->and($paths)->toHaveKey('medium')
        ->and($paths)->toHaveKey('large')
        ->and($paths)->toHaveKey('apple-touch');

    // Verify files exist
    foreach ($paths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeTrue();
    }
});

test('tenant can remove logo', function (): void {
    $paths = uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();
    expect($this->tenant->hasLogo())->toBeTrue();

    // Remove the logo
    $this->tenant->clearLogo();
    $this->tenant->refresh();

    expect($this->tenant->hasLogo())->toBeFalse()
        ->and($this->tenant->logo)->toBeNull();

    // Verify files were deleted
    foreach ($paths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeFalse();
    }
});

// ============================================
// LOGO URL TESTS
// ============================================

test('getLogoUrl returns correct URL for each size', function (): void {
    uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();

    $sizes = ['favicon', 'small', 'medium', 'large', 'apple-touch'];

    foreach ($sizes as $size) {
        $url = $this->tenant->getLogoUrl($size);
        expect($url)->not->toBeNull()
            ->and($url)->toContain('storage/')
            ->and($url)->toContain("logo-{$size}.png");
    }
});

test('getLogoUrl returns null when no logo exists', function (): void {
    $url = $this->tenant->getLogoUrl('medium');

    expect($url)->toBeNull();
});

test('getLogoUrl returns null for invalid size', function (): void {
    uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();

    $url = $this->tenant->getLogoUrl('nonexistent-size');

    expect($url)->toBeNull();
});

// ============================================
// HELPER METHOD TESTS
// ============================================

test('hasLogo returns true when logo exists', function (): void {
    uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();

    expect($this->tenant->hasLogo())->toBeTrue();
});

test('hasLogo returns false when no logo', function (): void {
    expect($this->tenant->hasLogo())->toBeFalse();
});

test('hasLogo returns false after logo is removed', function (): void {
    uploadLogoForTenant($this->tenant);

    $this->tenant->refresh();
    expect($this->tenant->hasLogo())->toBeTrue();

    $this->tenant->clearLogo();
    $this->tenant->refresh();

    expect($this->tenant->hasLogo())->toBeFalse();
});

// ============================================
// IMAGE PROCESSING SERVICE TESTS
// ============================================

test('ImageProcessingService validates logo minimum dimensions', function (): void {
    $service = new ImageProcessingService;

    // Create an image smaller than 256x256
    $file = UploadedFile::fake()->image('logo.png', 100, 100);

    $errors = $service->validateLogo($file);

    expect($errors)->toHaveKey('dimensions');
});

test('ImageProcessingService validates logo file type', function (): void {
    $service = new ImageProcessingService;

    // Create a fake text file
    $file = UploadedFile::fake()->create('logo.txt', 100);

    $errors = $service->validateLogo($file);

    expect($errors)->toHaveKey('type');
});

test('setLogoPaths updates tenant logo', function (): void {
    $paths = [
        'favicon' => 'logos/test/favicon.png',
        'small' => 'logos/test/small.png',
        'medium' => 'logos/test/medium.png',
    ];

    $this->tenant->setLogoPaths($paths);
    $this->tenant->refresh();

    expect($this->tenant->logo)->toBeArray()
        ->and($this->tenant->logo)->toHaveKey('favicon')
        ->and($this->tenant->logo['favicon'])->toBe('logos/test/favicon.png');
});
