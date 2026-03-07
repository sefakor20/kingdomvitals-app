<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageStagingService
{
    /**
     * The directory where staged files are stored.
     */
    private const STAGING_DIR = 'storage/app/image-staging';

    /**
     * Stage an uploaded file for background processing.
     *
     * Copies the file to a persistent staging location since Livewire's
     * temporary files are cleaned up after the request.
     */
    public function stageForProcessing(TemporaryUploadedFile $file, string $prefix): string
    {
        $stagingDir = base_path(self::STAGING_DIR);

        if (! is_dir($stagingDir)) {
            mkdir($stagingDir, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'tmp';
        $filename = $prefix.'_'.Str::random(40).'.'.$extension;
        $stagingPath = $stagingDir.'/'.$filename;

        copy($file->getRealPath(), $stagingPath);

        return $stagingPath;
    }

    /**
     * Clean up a staged file after processing.
     */
    public function cleanup(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Clean up all staged files older than the specified minutes.
     */
    public function cleanupOldFiles(int $olderThanMinutes = 60): int
    {
        $stagingDir = base_path(self::STAGING_DIR);

        if (! is_dir($stagingDir)) {
            return 0;
        }

        $count = 0;
        $cutoff = time() - ($olderThanMinutes * 60);

        foreach (glob($stagingDir.'/*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the staging directory path.
     */
    public function getStagingDirectory(): string
    {
        return base_path(self::STAGING_DIR);
    }
}
