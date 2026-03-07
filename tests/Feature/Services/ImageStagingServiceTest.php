<?php

use App\Services\ImageStagingService;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

beforeEach(function (): void {
    $this->service = app(ImageStagingService::class);

    // Ensure staging directory exists
    $stagingDir = base_path('storage/app/image-staging');
    if (! is_dir($stagingDir)) {
        mkdir($stagingDir, 0755, true);
    }
});

afterEach(function (): void {
    // Clean up staging directory
    $stagingDir = base_path('storage/app/image-staging');
    if (is_dir($stagingDir)) {
        foreach (glob($stagingDir.'/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
});

test('stageForProcessing creates file with correct prefix', function (): void {
    $file = UploadedFile::fake()->image('test.png', 100, 100);

    // Create a mock TemporaryUploadedFile
    $tempFile = new class($file) extends TemporaryUploadedFile
    {
        private $uploadedFile;

        public function __construct($file)
        {
            $this->uploadedFile = $file;
        }

        public function getRealPath(): string|false
        {
            return $this->uploadedFile->getRealPath();
        }

        public function getClientOriginalExtension(): string
        {
            return $this->uploadedFile->getClientOriginalExtension();
        }
    };

    $stagedPath = $this->service->stageForProcessing($tempFile, 'test-prefix');

    expect($stagedPath)->toContain('storage/app/image-staging');
    expect($stagedPath)->toContain('test-prefix_');
    expect($stagedPath)->toContain('.png');
    expect(file_exists($stagedPath))->toBeTrue();
});

test('cleanup removes staged file', function (): void {
    $stagingDir = base_path('storage/app/image-staging');
    $testFile = $stagingDir.'/test-cleanup-'.time().'.png';
    file_put_contents($testFile, 'test content');

    expect(file_exists($testFile))->toBeTrue();

    $this->service->cleanup($testFile);

    expect(file_exists($testFile))->toBeFalse();
});

test('cleanup handles non-existent file gracefully', function (): void {
    $nonExistentPath = '/tmp/non-existent-file-'.time().'.png';

    // Should not throw exception
    $this->service->cleanup($nonExistentPath);

    expect(true)->toBeTrue();
});

test('cleanupOldFiles removes files older than threshold', function (): void {
    $stagingDir = base_path('storage/app/image-staging');

    // Create an old file (simulate by changing mtime)
    $oldFile = $stagingDir.'/old-file-'.time().'.png';
    file_put_contents($oldFile, 'old content');
    touch($oldFile, time() - 3700); // 1 hour + a bit ago

    // Create a recent file
    $newFile = $stagingDir.'/new-file-'.time().'.png';
    file_put_contents($newFile, 'new content');

    $count = $this->service->cleanupOldFiles(60);

    expect($count)->toBe(1);
    expect(file_exists($oldFile))->toBeFalse();
    expect(file_exists($newFile))->toBeTrue();

    // Clean up new file
    unlink($newFile);
});

test('getStagingDirectory returns correct path', function (): void {
    $dir = $this->service->getStagingDirectory();

    expect($dir)->toContain('storage/app/image-staging');
});
