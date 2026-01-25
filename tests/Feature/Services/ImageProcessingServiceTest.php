<?php

declare(strict_types=1);

use App\Services\ImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

test('logo sizes are defined correctly', function (): void {
    $service = new ImageProcessingService;

    $sizes = $service->getAvailableSizes();

    expect($sizes)->toHaveKey('favicon')
        ->and($sizes['favicon'])->toBe(32)
        ->and($sizes)->toHaveKey('small')
        ->and($sizes['small'])->toBe(64)
        ->and($sizes)->toHaveKey('medium')
        ->and($sizes['medium'])->toBe(128)
        ->and($sizes)->toHaveKey('large')
        ->and($sizes['large'])->toBe(256)
        ->and($sizes)->toHaveKey('apple-touch')
        ->and($sizes['apple-touch'])->toBe(180);
});

test('validates logo file type', function (): void {
    $service = new ImageProcessingService;

    // Create a fake text file pretending to be an image
    $file = UploadedFile::fake()->create('logo.txt', 100);

    $errors = $service->validateLogo($file);

    expect($errors)->toHaveKey('type');
});

test('validates logo file size', function (): void {
    $service = new ImageProcessingService;

    // Create a fake image larger than 2MB
    $file = UploadedFile::fake()->image('logo.png', 500, 500)->size(3000); // 3MB

    $errors = $service->validateLogo($file);

    expect($errors)->toHaveKey('size');
});

test('validates logo minimum dimensions', function (): void {
    $service = new ImageProcessingService;

    // Create a fake image smaller than 256x256
    $file = UploadedFile::fake()->image('logo.png', 100, 100);

    $errors = $service->validateLogo($file);

    expect($errors)->toHaveKey('dimensions');
});

test('validates logo with all checks passing', function (): void {
    $service = new ImageProcessingService;

    // Create a valid fake image
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $errors = $service->validateLogo($file);

    expect($errors)->toBeEmpty();
});

test('processes logo and creates all sizes', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/test');

    expect($paths)->toHaveCount(5)
        ->and($paths)->toHaveKey('favicon')
        ->and($paths)->toHaveKey('small')
        ->and($paths)->toHaveKey('medium')
        ->and($paths)->toHaveKey('large')
        ->and($paths)->toHaveKey('apple-touch');

    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('deletes logo files by path', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/test');

    // Verify files exist
    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }

    // Delete
    $service->deleteLogoByPaths($paths);

    // Verify files are deleted
    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
});

test('deletes logo files by base path', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/test');

    // Verify files exist
    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }

    // Delete using base path
    $service->deleteLogo('logos/test');

    // Verify files are deleted
    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
});

test('gets logo url for specific size', function (): void {
    $service = new ImageProcessingService;
    $file = UploadedFile::fake()->image('logo.png', 512, 512);

    $paths = $service->processLogo($file, 'logos/test');

    $url = $service->getLogoUrl($paths, 'small');

    expect($url)->not->toBeNull()
        ->and($url)->toContain('logo-small.png');
});

test('returns null for missing logo size', function (): void {
    $service = new ImageProcessingService;

    $url = $service->getLogoUrl(null, 'small');

    expect($url)->toBeNull();
});

test('returns null for non-existent path', function (): void {
    $service = new ImageProcessingService;

    $paths = [
        'small' => 'logos/nonexistent/logo-small.png',
    ];

    $url = $service->getLogoUrl($paths, 'small');

    expect($url)->toBeNull();
});
