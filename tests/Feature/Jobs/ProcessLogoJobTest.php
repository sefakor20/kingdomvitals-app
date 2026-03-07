<?php

use App\Jobs\ProcessLogoJob;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Http\UploadedFile;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    // Clean up any existing logos
    $logoDir = base_path("storage/app/public/logos/tenants/{$this->tenant->id}");
    if (is_dir($logoDir)) {
        array_map('unlink', glob($logoDir.'/*'));
    }
});

afterEach(function (): void {
    // Clean up logo files
    $logoDir = base_path("storage/app/public/logos/tenants/{$this->tenant->id}");
    if (is_dir($logoDir)) {
        array_map('unlink', glob($logoDir.'/*'));
        rmdir($logoDir);
    }

    // Clean up staging directory
    $stagingDir = base_path('storage/app/image-staging');
    if (is_dir($stagingDir)) {
        foreach (glob($stagingDir.'/*') as $file) {
            unlink($file);
        }
    }

    $this->tearDownTestTenant();
});

test('job processes logo and creates all size variants', function (): void {
    // Create a test image file
    $image = UploadedFile::fake()->image('logo.png', 300, 300);

    // Stage the file
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'test-logo');

    $processingToken = 'test-token-'.time();

    // Set up cache state (use file store like the actual code)
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing_token", $processingToken, now()->addMinutes(5));

    // Run the job
    $job = new ProcessLogoJob(
        tenantId: $this->tenant->id,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
    );
    $job->handle(app(ImageStagingService::class));

    // Assert all logo sizes were created
    $logoDir = base_path("storage/app/public/logos/tenants/{$this->tenant->id}");
    expect(file_exists($logoDir.'/logo-favicon.png'))->toBeTrue();
    expect(file_exists($logoDir.'/logo-small.png'))->toBeTrue();
    expect(file_exists($logoDir.'/logo-medium.png'))->toBeTrue();
    expect(file_exists($logoDir.'/logo-large.png'))->toBeTrue();
    expect(file_exists($logoDir.'/logo-apple-touch.png'))->toBeTrue();

    // Assert tenant was updated
    $this->tenant->refresh();
    expect($this->tenant->hasLogo())->toBeTrue();
    expect($this->tenant->logo)->toHaveKeys(['favicon', 'small', 'medium', 'large', 'apple-touch']);

    // Assert cache was cleared
    expect(cache()->store('file')->has("tenant:{$this->tenant->id}:logo_processing"))->toBeFalse();
    expect(cache()->store('file')->has("tenant:{$this->tenant->id}:logo_processing_token"))->toBeFalse();

    // Assert staged file was cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});

test('job is skipped when superseded by newer upload', function (): void {
    $image = UploadedFile::fake()->image('logo.png', 300, 300);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'test-logo');

    $oldToken = 'old-token';
    $newToken = 'new-token';

    // Set cache with newer token (simulating re-upload)
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing_token", $newToken, now()->addMinutes(5));

    // Run job with old token
    $job = new ProcessLogoJob(
        tenantId: $this->tenant->id,
        tempFilePath: $stagedPath,
        processingToken: $oldToken,
    );
    $job->handle(app(ImageStagingService::class));

    // Assert tenant was NOT updated
    $this->tenant->refresh();
    expect($this->tenant->hasLogo())->toBeFalse();

    // Assert staged file was cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});

test('job deletes old logo files when replacing', function (): void {
    // Create existing logo files
    $logoDir = base_path("storage/app/public/logos/tenants/{$this->tenant->id}");
    if (! is_dir($logoDir)) {
        mkdir($logoDir, 0755, true);
    }

    $existingPaths = [];
    foreach (ImageProcessingService::LOGO_SIZES as $sizeName => $size) {
        $filename = "logo-{$sizeName}.png";
        file_put_contents($logoDir.'/'.$filename, 'old content');
        $existingPaths[$sizeName] = "logos/tenants/{$this->tenant->id}/{$filename}";
    }

    // Create new image
    $image = UploadedFile::fake()->image('new-logo.png', 300, 300);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'test-logo');

    $processingToken = 'test-token-'.time();
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessLogoJob(
        tenantId: $this->tenant->id,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
        existingLogoPaths: $existingPaths,
    );
    $job->handle(app(ImageStagingService::class));

    // Assert new files exist (they should have new content, not 'old content')
    foreach (ImageProcessingService::LOGO_SIZES as $sizeName => $size) {
        $filename = "logo-{$sizeName}.png";
        expect(file_exists($logoDir.'/'.$filename))->toBeTrue();
        expect(file_get_contents($logoDir.'/'.$filename))->not->toBe('old content');
    }
});

test('failed job stores error in cache', function (): void {
    $nonExistentPath = '/tmp/non-existent-file-'.time().'.png';
    $processingToken = 'test-token';

    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("tenant:{$this->tenant->id}:logo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessLogoJob(
        tenantId: $this->tenant->id,
        tempFilePath: $nonExistentPath,
        processingToken: $processingToken,
    );

    // Run the job (should clear state when file not found)
    $job->handle(app(ImageStagingService::class));

    // Assert processing state was cleared
    expect(cache()->store('file')->has("tenant:{$this->tenant->id}:logo_processing"))->toBeFalse();
});

test('job handles non-existent tenant gracefully', function (): void {
    $image = UploadedFile::fake()->image('logo.png', 300, 300);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'test-logo');

    $nonExistentTenantId = 'non-existent-tenant-'.time();
    $processingToken = 'test-token';

    cache()->store('file')->put("tenant:{$nonExistentTenantId}:logo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessLogoJob(
        tenantId: $nonExistentTenantId,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
    );

    // Should not throw exception
    $job->handle(app(ImageStagingService::class));

    // Staged file should be cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});
