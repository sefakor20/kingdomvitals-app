<?php

use App\Services\ImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

beforeEach(function (): void {
    Storage::fake('local');
});

test('processMemberPhoto crops and resizes to 256x256 square', function (): void {
    $service = app(ImageProcessingService::class);

    // Create a tall portrait image (300x600)
    $file = UploadedFile::fake()->image('portrait.jpg', 300, 600);

    $result = $service->processMemberPhoto($file);

    // Parse the result as an image
    $processedImage = Image::read($result);

    expect($processedImage->width())->toBe(256);
    expect($processedImage->height())->toBe(256);
});

test('processMemberPhoto handles landscape images', function (): void {
    $service = app(ImageProcessingService::class);

    // Create a wide landscape image (800x400)
    $file = UploadedFile::fake()->image('landscape.jpg', 800, 400);

    $result = $service->processMemberPhoto($file);

    $processedImage = Image::read($result);

    expect($processedImage->width())->toBe(256);
    expect($processedImage->height())->toBe(256);
});

test('processMemberPhoto upscales small images', function (): void {
    $service = app(ImageProcessingService::class);

    // Create a small image (100x100)
    $file = UploadedFile::fake()->image('small.jpg', 100, 100);

    $result = $service->processMemberPhoto($file);

    $processedImage = Image::read($result);

    expect($processedImage->width())->toBe(256);
    expect($processedImage->height())->toBe(256);
});

test('processMemberPhoto outputs JPEG format', function (): void {
    $service = app(ImageProcessingService::class);

    // Create a PNG image
    $file = UploadedFile::fake()->image('photo.png', 400, 400);

    $result = $service->processMemberPhoto($file);

    // JPEG files start with FFD8FF magic bytes
    $magicBytes = bin2hex(substr($result, 0, 3));

    expect($magicBytes)->toBe('ffd8ff');
});
