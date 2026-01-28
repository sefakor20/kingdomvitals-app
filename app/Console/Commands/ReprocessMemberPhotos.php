<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Member;
use App\Services\ImageProcessingService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReprocessMemberPhotos extends Command
{
    protected $signature = 'members:reprocess-photos {--tenant= : Specific tenant ID to process}';

    protected $description = 'Re-process existing member photos to square format (256x256)';

    public function handle(ImageProcessingService $service): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return Command::FAILURE;
        }

        $totalProcessed = 0;
        $totalSkipped = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name} ({$tenant->id})");

            $tenant->run(function () use ($service, &$totalProcessed, &$totalSkipped): void {
                $members = Member::whereNotNull('photo_url')->get();

                if ($members->isEmpty()) {
                    $this->line('  No members with photos found.');

                    return;
                }

                $bar = $this->output->createProgressBar($members->count());
                $bar->start();

                foreach ($members as $member) {
                    $relativePath = str_replace('/storage/', '', $member->photo_url);
                    $fullPath = base_path('storage/app/public/'.$relativePath);

                    if (! file_exists($fullPath)) {
                        $totalSkipped++;
                        $bar->advance();

                        continue;
                    }

                    try {
                        // Process the existing photo
                        $uploadedFile = new UploadedFile($fullPath, basename($fullPath));
                        $processed = $service->processMemberPhoto($uploadedFile);

                        // Generate new filename and save
                        $newFilename = Str::random(40).'.jpg';
                        $directory = dirname($fullPath);
                        $newPath = $directory.'/'.$newFilename;

                        file_put_contents($newPath, $processed);

                        // Update member record with new URL
                        $newUrl = '/storage/'.dirname($relativePath).'/'.$newFilename;
                        $member->update(['photo_url' => $newUrl]);

                        // Delete old file
                        @unlink($fullPath);

                        $totalProcessed++;
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->warn("  Failed to process photo for member {$member->id}: {$e->getMessage()}");
                        $totalSkipped++;
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
            });
        }

        $this->newLine();
        $this->info("Done! Processed: {$totalProcessed}, Skipped: {$totalSkipped}");

        return Command::SUCCESS;
    }
}
