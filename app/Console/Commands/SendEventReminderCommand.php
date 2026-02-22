<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Mail\EventReminderMail;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEventReminderCommand extends Command
{
    protected $signature = 'events:send-reminders {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send reminder emails to registered attendees for upcoming events (within 24 hours)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No emails will actually be sent');
        }

        $this->info('Starting event reminder job...');

        $totalSent = 0;
        $totalSkipped = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($dryRun, &$totalSent, &$totalSkipped): void {
            tenancy()->initialize($tenant);

            $this->line("Processing tenant: {$tenant->id}");

            Branch::all()->each(function (Branch $branch) use ($dryRun, &$totalSent, &$totalSkipped): void {
                $upcomingEvents = $this->getUpcomingEvents($branch);

                if ($upcomingEvents->isEmpty()) {
                    $this->line("  Branch {$branch->name}: No events within 24 hour window");

                    return;
                }

                $this->info("  Branch {$branch->name}: Found {$upcomingEvents->count()} upcoming event(s)");

                foreach ($upcomingEvents as $event) {
                    $this->processEvent($event, $branch, $dryRun, $totalSent, $totalSkipped);
                }
            });

            tenancy()->end();
        });

        $this->newLine();
        $this->info("Done! Sent {$totalSent} reminder(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }

    protected function getUpcomingEvents(Branch $branch): Collection
    {
        // Events starting within 24 hours that haven't been reminded yet
        $windowStart = now();
        $windowEnd = now()->addHours(24);

        return Event::where('branch_id', $branch->id)
            ->where('status', EventStatus::Published)
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->whereNull('reminder_sent_at')
            ->with(['registrations' => function ($query): void {
                $query->where('status', RegistrationStatus::Registered)
                    ->orWhere('status', RegistrationStatus::Attended);
            }])
            ->get();
    }

    protected function processEvent(Event $event, Branch $branch, bool $dryRun, int &$totalSent, int &$totalSkipped): void
    {
        $this->line("    Event: {$event->name} (starts {$event->starts_at->format('M j, Y g:i A')})");

        $registrations = $event->registrations->filter(function ($registration) {
            // Only send to registrations with email
            $email = $registration->guest_email
                ?? $registration->member?->email
                ?? $registration->visitor?->email;

            return ! empty($email);
        });

        if ($registrations->isEmpty()) {
            $this->line('      No registrations with email addresses');
            $totalSkipped++;

            return;
        }

        $sentCount = 0;

        foreach ($registrations as $registration) {
            $email = $registration->guest_email
                ?? $registration->member?->email
                ?? $registration->visitor?->email;

            if ($dryRun) {
                $this->line("      - Would email {$registration->attendee_name} ({$email})");
                $sentCount++;

                continue;
            }

            try {
                Mail::to($email)->queue(new EventReminderMail($registration));
                $this->line("      - Sent reminder to {$registration->attendee_name} ({$email})");
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("      - Failed to send to {$email}: {$e->getMessage()}");
                Log::error('Event reminder email failed', [
                    'registration_id' => $registration->id,
                    'event_id' => $event->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $totalSent += $sentCount;

        // Mark event as reminded (only if not dry run and we sent at least one email)
        if (! $dryRun && $sentCount > 0) {
            $event->update(['reminder_sent_at' => now()]);
            $this->line('      Marked event as reminded');
        }
    }
}
