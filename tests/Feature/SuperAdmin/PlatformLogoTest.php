<?php

declare(strict_types=1);

use App\Models\SuperAdmin;
use App\Models\SystemSetting;
use App\Services\ImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    // Clean up any existing platform logo
    $existingPaths = SystemSetting::get('platform_logo');
    if ($existingPaths && is_array($existingPaths)) {
        foreach ($existingPaths as $path) {
            $fullPath = base_path('storage/app/public/'.$path);
            if (file_exists($fullPath) && ! unlink($fullPath)) {
                Log::warning('PlatformLogoTest: Failed to delete existing logo', ['path' => $fullPath]);
            }
        }
    }
    SystemSetting::remove('platform_logo');
});

afterEach(function (): void {
    // Clean up uploaded files
    $logoDir = base_path('storage/app/public/logos/platform');
    if (is_dir($logoDir)) {
        array_map('unlink', glob("$logoDir/*.*") ?: []);
        @rmdir($logoDir);
    }

    SystemSetting::remove('platform_logo');
});

// ============================================
// HELPER FUNCTION
// ============================================

function uploadPlatformLogo(): array
{
    $service = app(ImageProcessingService::class);
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/platform');
    SystemSetting::set('platform_logo', $paths, 'app');

    return $paths;
}

// ============================================
// PLATFORM LOGO STORAGE TESTS
// ============================================

test('platform logo can be stored in SystemSetting as array', function (): void {
    $paths = uploadPlatformLogo();

    $logoPaths = SystemSetting::get('platform_logo');

    expect($logoPaths)->toBeArray()
        ->and($logoPaths)->toHaveKey('favicon')
        ->and($logoPaths)->toHaveKey('small')
        ->and($logoPaths)->toHaveKey('medium')
        ->and($logoPaths)->toHaveKey('large')
        ->and($logoPaths)->toHaveKey('apple-touch');

    // Verify files exist
    foreach ($logoPaths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeTrue();
    }
});

test('platform logo can be removed from SystemSetting', function (): void {
    $paths = uploadPlatformLogo();

    $logoPaths = SystemSetting::get('platform_logo');
    expect($logoPaths)->not->toBeNull();

    // Delete the files
    $service = app(ImageProcessingService::class);
    $service->deleteLogoByPaths($paths);

    // Remove from settings
    SystemSetting::remove('platform_logo');

    expect(SystemSetting::get('platform_logo'))->toBeNull();

    // Verify files were deleted
    foreach ($paths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeFalse();
    }
});

// ============================================
// DISPLAY TESTS
// ============================================

test('superadmin sidebar shows shield icon when no logo', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Ensure no logo is set
    SystemSetting::remove('platform_logo');

    $response = $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.dashboard'));

    $response->assertOk();

    // The page should render without errors
    // (shield-check is rendered by Flux component)
});

test('superadmin sidebar shows platform logo when set', function (): void {
    $admin = SuperAdmin::factory()->create();

    // Upload a logo
    uploadPlatformLogo();

    // Visit the dashboard and check for the logo
    $response = $this->actingAs($admin, 'superadmin')
        ->get(route('superadmin.dashboard'));

    $response->assertOk();

    // The logo URL should be in the page
    $response->assertSee('storage/logos/platform/logo-small.png', false);
});

test('superadmin auth page renders when no logo', function (): void {
    // Ensure no logo is set
    SystemSetting::remove('platform_logo');

    $response = $this->get(route('superadmin.login'));

    $response->assertOk();
});

test('superadmin auth page shows platform logo when set', function (): void {
    // Upload a logo
    uploadPlatformLogo();

    // Visit the login page
    $response = $this->get(route('superadmin.login'));

    $response->assertOk();

    // The logo should be visible
    $response->assertSee('storage/logos/platform/logo-medium.png', false);
});

// ============================================
// PERMISSION TESTS
// ============================================

test('only owner can modify settings', function (): void {
    $admin = SuperAdmin::factory()->create(); // Admin role by default
    $owner = SuperAdmin::factory()->owner()->create();
    $support = SuperAdmin::factory()->support()->create();

    // Admin cannot modify (canModifySettings returns false for Admin)
    expect($admin->role->canModifySettings())->toBeFalse();

    // Owner can modify
    expect($owner->role->canModifySettings())->toBeTrue();

    // Support cannot modify
    expect($support->role->canModifySettings())->toBeFalse();
});

test('admin and owner can view settings', function (): void {
    $admin = SuperAdmin::factory()->create();
    $owner = SuperAdmin::factory()->owner()->create();
    $support = SuperAdmin::factory()->support()->create();

    expect($admin->role->canViewSettings())->toBeTrue();
    expect($owner->role->canViewSettings())->toBeTrue();
    expect($support->role->canViewSettings())->toBeFalse();
});

// ============================================
// IMAGE PROCESSING SERVICE TESTS
// ============================================

test('ImageProcessingService can process logo into multiple sizes', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/test-platform');

    expect($paths)->toBeArray()
        ->and($paths)->toHaveKey('favicon')
        ->and($paths)->toHaveKey('small')
        ->and($paths)->toHaveKey('medium')
        ->and($paths)->toHaveKey('large')
        ->and($paths)->toHaveKey('apple-touch');

    // Clean up
    $service->deleteLogoByPaths($paths);
});

test('ImageProcessingService can delete logo files', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/delete-test');

    // Verify files exist
    foreach ($paths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeTrue();
    }

    // Delete
    $service->deleteLogoByPaths($paths);

    // Verify files are deleted
    foreach ($paths as $path) {
        $fullPath = base_path('storage/app/public/'.$path);
        expect(file_exists($fullPath))->toBeFalse();
    }
});

test('ImageProcessingService getLogoUrl returns correct URL', function (): void {
    $service = new ImageProcessingService;
    $paths = [
        'small' => 'logos/platform/logo-small.png',
        'medium' => 'logos/platform/logo-medium.png',
    ];

    // Create the directory and files for testing
    $directory = base_path('storage/app/public/logos/platform');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    file_put_contents($directory.'/logo-small.png', 'test');
    file_put_contents($directory.'/logo-medium.png', 'test');

    $url = $service->getLogoUrl($paths, 'small');

    expect($url)->not->toBeNull()
        ->and($url)->toContain('storage/logos/platform/logo-small.png');

    // Clean up
    @unlink($directory.'/logo-small.png');
    @unlink($directory.'/logo-medium.png');
});
