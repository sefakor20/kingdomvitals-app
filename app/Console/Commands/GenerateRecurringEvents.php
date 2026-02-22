<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Tenant\Event;
use App\Services\EventRecurrenceService;
use Illuminate\Console\Command;

class GenerateRecurringEvents extends Command
{
    protected $signature = 'events:generate-recurring {--months=3 : Number of months ahead to generate}';

    protected $description = 'Generate future occurrences for all recurring events across all tenants';

    public function handle(EventRecurrenceService $recurrenceService): int
    {
        $months = (int) $this->option('months');
        $totalGenerated = 0;
        $totalCleaned = 0;

        $this->info("Generating recurring event occurrences for the next {$months} months...");

        $tenants = Tenant::all();

        $this->withProgressBar($tenants, function (Tenant $tenant) use ($recurrenceService, $months, &$totalGenerated, &$totalCleaned): void {
            $tenant->run(function () use ($recurrenceService, $months, &$totalGenerated, &$totalCleaned): void {
                // Get all active recurring parent events
                $recurringEvents = Event::recurringParents()
                    ->whereIn('status', ['published', 'ongoing'])
                    ->get();

                foreach ($recurringEvents as $parentEvent) {
                    // Generate new occurrences
                    $occurrences = $recurrenceService->generateOccurrences($parentEvent, $months);
                    $totalGenerated += $occurrences->count();

                    // Clean up orphaned occurrences (beyond end date or count)
                    $cleaned = $recurrenceService->deleteOrphanedOccurrences($parentEvent);
                    $totalCleaned += $cleaned;
                }
            });
        });

        $this->newLine(2);
        $this->info("Generated {$totalGenerated} new event occurrences.");

        if ($totalCleaned > 0) {
            $this->info("Cleaned up {$totalCleaned} orphaned occurrences.");
        }

        return self::SUCCESS;
    }
}
