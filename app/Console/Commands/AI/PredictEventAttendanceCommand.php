<?php

declare(strict_types=1);

namespace App\Console\Commands\AI;

use App\Jobs\AI\PredictEventAttendanceJob;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use App\Services\AI\EventPredictionService;
use Illuminate\Console\Command;

class PredictEventAttendanceCommand extends Command
{
    protected $signature = 'ai:predict-event-attendance
                            {--tenant= : Specific tenant ID to process}
                            {--branch= : Specific branch ID to process}
                            {--event= : Specific event ID to process}
                            {--days-ahead=3 : Number of days ahead to look for events}
                            {--sync : Run synchronously instead of dispatching jobs}';

    protected $description = 'Predict event attendance probability for upcoming events';

    public function handle(EventPredictionService $service): int
    {
        if (! $service->isEnabled()) {
            $this->warn('Event prediction feature is disabled.');

            return Command::SUCCESS;
        }

        $tenantId = $this->option('tenant');
        $branchId = $this->option('branch');
        $eventId = $this->option('event');
        $daysAhead = (int) $this->option('days-ahead');
        $sync = $this->option('sync');

        $this->info("Predicting event attendance (looking {$daysAhead} days ahead)...");

        // If a specific event is provided, process just that
        if ($eventId) {
            $this->processEvent($eventId, $sync);
            $this->info('Done!');

            return Command::SUCCESS;
        }

        // If a specific branch is provided, process events for that branch
        if ($branchId) {
            $this->processBranchEvents($branchId, $daysAhead, $sync);
            $this->info('Done!');

            return Command::SUCCESS;
        }

        // Process tenants
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to process.');

            return Command::SUCCESS;
        }

        $totalEvents = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $branches = Branch::all();

            foreach ($branches as $branch) {
                $count = $this->processBranchEvents($branch->id, $daysAhead, $sync);
                $totalEvents += $count;
            }

            tenancy()->end();
        }

        $this->info("Done! Processed {$totalEvents} event(s).");

        return Command::SUCCESS;
    }

    protected function processBranchEvents(string $branchId, int $daysAhead, bool $sync): int
    {
        $events = Event::query()
            ->where('branch_id', $branchId)
            ->whereBetween('starts_at', [now(), now()->addDays($daysAhead)])
            ->get();

        foreach ($events as $event) {
            $this->processEvent($event->id, $sync);
        }

        return $events->count();
    }

    protected function processEvent(string $eventId, bool $sync): void
    {
        $this->line("  - Predicting attendance for event {$eventId}");

        $job = new PredictEventAttendanceJob($eventId);

        if ($sync) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }
    }
}
