<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageProcessingService
{
    /**
     * Logo sizes following Laravel conventions.
     * These are the standard sizes needed for various display contexts.
     */
    public const LOGO_SIZES = [
        'favicon' => 32,      // favicon size
        'small' => 64,        // sidebar/nav logo
        'medium' => 128,      // medium displays
        'large' => 256,       // large displays
        'apple-touch' => 180, // apple-touch-icon
    ];

    /**
     * Process a logo image and generate multiple sizes.
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file  The uploaded image file
     * @param  string  $basePath  Base path for storing (e.g., 'logos/platform' or 'logos')
     * @param  string  $disk  Storage disk to use
     * @return array<string, string> Array of size names to stored paths
     */
    public function processLogo(UploadedFile|TemporaryUploadedFile $file, string $basePath, string $disk = 'public'): array
    {
        $paths = [];

        foreach (self::LOGO_SIZES as $sizeName => $targetSize) {
            // Create a copy of the image for this size
            $resized = Image::read($file->getRealPath());

            // Cover crop: resize and crop to fit exactly the target dimensions
            $resized->cover($targetSize, $targetSize);

            // Generate filename
            $filename = "logo-{$sizeName}.png";
            $fullPath = rtrim($basePath, '/').'/'.$filename;

            // Encode as PNG for best quality with transparency support
            $encoded = $resized->encode(new PngEncoder);

            // Store the image
            Storage::disk($disk)->put($fullPath, (string) $encoded);

            $paths[$sizeName] = $fullPath;
        }

        return $paths;
    }

    /**
     * Delete all logo size variants from storage.
     *
     * @param  string  $basePath  Base path where logos are stored
     * @param  string  $disk  Storage disk
     */
    public function deleteLogo(string $basePath, string $disk = 'public'): void
    {
        foreach (array_keys(self::LOGO_SIZES) as $sizeName) {
            $filename = "logo-{$sizeName}.png";
            $fullPath = rtrim($basePath, '/').'/'.$filename;

            if (Storage::disk($disk)->exists($fullPath)) {
                Storage::disk($disk)->delete($fullPath);
            }
        }
    }

    /**
     * Delete logo files using paths array.
     *
     * @param  array<string, string>  $paths  Array of size names to paths
     * @param  string  $disk  Storage disk
     */
    public function deleteLogoByPaths(array $paths, string $disk = 'public'): void
    {
        foreach ($paths as $path) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * Get the URL for a specific logo size.
     *
     * @param  array<string, string>|null  $paths  Array of size names to paths
     * @param  string  $size  The size to get (favicon, small, medium, large, apple-touch)
     * @param  string  $disk  Storage disk
     * @return string|null The URL or null if not found
     */
    public function getLogoUrl(?array $paths, string $size = 'small', string $disk = 'public'): ?string
    {
        if ($paths === null || $paths === [] || ! isset($paths[$size])) {
            return null;
        }

        $path = $paths[$size];

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Validate an uploaded logo file.
     *
     * @return array<string, string> Validation errors (empty if valid)
     */
    public function validateLogo(UploadedFile|TemporaryUploadedFile $file): array
    {
        $errors = [];

        // Check file type
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $allowedMimes, true)) {
            $errors['type'] = __('Logo must be a PNG, JPG, or WebP image.');
        }

        // Check file size (max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file->getSize() > $maxSize) {
            $errors['size'] = __('Logo must be less than 2MB.');
        }

        // Check minimum dimensions
        try {
            $image = Image::read($file->getRealPath());
            $minDimension = 256;

            if ($image->width() < $minDimension || $image->height() < $minDimension) {
                $errors['dimensions'] = __('Logo must be at least :size x :size pixels.', ['size' => $minDimension]);
            }
        } catch (\Exception $e) {
            Log::warning('ImageProcessingService::validateLogo: Failed to read image', [
                'error' => $e->getMessage(),
                'file_path' => $file->getRealPath(),
            ]);
            $errors['read'] = __('Unable to read image file.');
        }

        return $errors;
    }

    /**
     * Get available logo sizes.
     *
     * @return array<string, int>
     */
    public function getAvailableSizes(): array
    {
        return self::LOGO_SIZES;
    }

    /**
     * Process a member photo by cropping to square and resizing.
     *
     * This creates a consistently sized square image that works well for both
     * circular avatars (member index, profile) and rounded squares (ID cards).
     *
     * @param  UploadedFile|TemporaryUploadedFile  $file  The uploaded photo
     * @return string The processed image as a binary string (JPEG format)
     */
    public function processMemberPhoto(UploadedFile|TemporaryUploadedFile $file): string
    {
        $image = Image::read($file->getRealPath());

        // Cover crop to square (256x256 for 2x retina support on largest display of 80x80)
        $image->cover(256, 256);

        // Encode as JPEG with 85% quality (good balance of size/quality for photos)
        return (string) $image->encode(new JpegEncoder(85));
    }
}
