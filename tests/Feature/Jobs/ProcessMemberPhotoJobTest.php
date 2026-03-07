<?php

use App\Jobs\ProcessMemberPhotoJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Services\ImageProcessingService;
use App\Services\ImageStagingService;
use Illuminate\Http\UploadedFile;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->main()->create();
    $this->member = Member::factory()->create([
        'primary_branch_id' => $this->branch->id,
    ]);
});

afterEach(function (): void {
    // Clean up member photo files
    $photoDir = base_path("storage/app/public/members/{$this->tenant->id}");
    if (is_dir($photoDir)) {
        array_map('unlink', glob($photoDir.'/*.jpg'));
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

test('job processes member photo and updates member', function (): void {
    // Create a test image file
    $image = UploadedFile::fake()->image('photo.jpg', 400, 400);

    // Stage the file
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'member-photo');

    $processingToken = 'test-token-'.time();

    // Set up cache state
    cache()->store('file')->put("member:{$this->member->id}:photo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("member:{$this->member->id}:photo_processing_token", $processingToken, now()->addMinutes(5));

    // Run the job
    $job = new ProcessMemberPhotoJob(
        tenantId: $this->tenant->id,
        memberId: $this->member->id,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
    );
    $job->handle(app(ImageProcessingService::class), app(ImageStagingService::class));

    // Assert member was updated with photo URL
    $this->member->refresh();
    expect($this->member->photo_url)->not->toBeNull();
    expect($this->member->photo_url)->toContain('/storage/members/');
    expect($this->member->photo_url)->toContain('.jpg');

    // Assert photo file exists
    $relativePath = str_replace('/storage/', '', $this->member->photo_url);
    $fullPath = base_path('storage/app/public/'.$relativePath);
    expect(file_exists($fullPath))->toBeTrue();

    // Assert cache was cleared
    expect(cache()->store('file')->has("member:{$this->member->id}:photo_processing"))->toBeFalse();
    expect(cache()->store('file')->has("member:{$this->member->id}:photo_processing_token"))->toBeFalse();

    // Assert staged file was cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});

test('job is skipped when superseded by newer upload', function (): void {
    $image = UploadedFile::fake()->image('photo.jpg', 400, 400);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'member-photo');

    $oldToken = 'old-token';
    $newToken = 'new-token';

    // Set cache with newer token (simulating re-upload)
    cache()->store('file')->put("member:{$this->member->id}:photo_processing_token", $newToken, now()->addMinutes(5));

    // Run job with old token
    $job = new ProcessMemberPhotoJob(
        tenantId: $this->tenant->id,
        memberId: $this->member->id,
        tempFilePath: $stagedPath,
        processingToken: $oldToken,
    );
    $job->handle(app(ImageProcessingService::class), app(ImageStagingService::class));

    // Assert member was NOT updated
    $this->member->refresh();
    expect($this->member->photo_url)->toBeNull();

    // Assert staged file was cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});

test('job deletes old photo when replacing', function (): void {
    // Create an old photo file
    $photoDir = base_path("storage/app/public/members/{$this->tenant->id}");
    if (! is_dir($photoDir)) {
        mkdir($photoDir, 0755, true);
    }
    $oldPhotoFilename = 'old-photo-'.time().'.jpg';
    $oldPhotoPath = $photoDir.'/'.$oldPhotoFilename;
    file_put_contents($oldPhotoPath, 'old photo content');

    $oldPhotoUrl = "/storage/members/{$this->tenant->id}/{$oldPhotoFilename}";

    // Create new image
    $image = UploadedFile::fake()->image('new-photo.jpg', 400, 400);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'member-photo');

    $processingToken = 'test-token-'.time();
    cache()->store('file')->put("member:{$this->member->id}:photo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("member:{$this->member->id}:photo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessMemberPhotoJob(
        tenantId: $this->tenant->id,
        memberId: $this->member->id,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
        oldPhotoUrl: $oldPhotoUrl,
    );
    $job->handle(app(ImageProcessingService::class), app(ImageStagingService::class));

    // Assert old photo was deleted
    expect(file_exists($oldPhotoPath))->toBeFalse();

    // Assert new photo exists
    $this->member->refresh();
    expect($this->member->photo_url)->not->toBeNull();
    expect($this->member->photo_url)->not->toBe($oldPhotoUrl);
});

test('failed job stores error in cache', function (): void {
    $nonExistentPath = '/tmp/non-existent-file-'.time().'.jpg';
    $processingToken = 'test-token';

    cache()->store('file')->put("member:{$this->member->id}:photo_processing", true, now()->addMinutes(5));
    cache()->store('file')->put("member:{$this->member->id}:photo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessMemberPhotoJob(
        tenantId: $this->tenant->id,
        memberId: $this->member->id,
        tempFilePath: $nonExistentPath,
        processingToken: $processingToken,
    );

    // Run the job (should clear state when file not found)
    $job->handle(app(ImageProcessingService::class), app(ImageStagingService::class));

    // Assert processing state was cleared
    expect(cache()->store('file')->has("member:{$this->member->id}:photo_processing"))->toBeFalse();
});

test('job handles non-existent member gracefully', function (): void {
    $image = UploadedFile::fake()->image('photo.jpg', 400, 400);
    $stagingService = app(ImageStagingService::class);
    $stagedPath = $stagingService->stageForProcessing($image, 'member-photo');

    $nonExistentMemberId = 'non-existent-member-'.time();
    $processingToken = 'test-token';

    cache()->store('file')->put("member:{$nonExistentMemberId}:photo_processing_token", $processingToken, now()->addMinutes(5));

    $job = new ProcessMemberPhotoJob(
        tenantId: $this->tenant->id,
        memberId: $nonExistentMemberId,
        tempFilePath: $stagedPath,
        processingToken: $processingToken,
    );

    // Should not throw exception
    $job->handle(app(ImageProcessingService::class), app(ImageStagingService::class));

    // Staged file should be cleaned up
    expect(file_exists($stagedPath))->toBeFalse();
});
