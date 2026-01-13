<?php

namespace App\Console\Commands;

use App\Enums\FollowUpOutcome;
use App\Models\Tenant;
use App\Models\Tenant\VisitorFollowUp;
use App\Notifications\VisitorFollowUpReminderNotification;
use Illuminate\Console\Command;

class SendFollowUpRemindersCommand extends Command
{
    protected $signature = 'visitors:send-follow-up-reminders {--hours=24 : Send reminders for follow-ups scheduled within this many hours}';

    protected $description = 'Send reminder notifications for upcoming visitor follow-ups';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $this->info("Sending follow-up reminders for the next {$hours} hours...");

        $totalSent = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($hours, &$totalSent): void {
            tenancy()->initialize($tenant);

            $followUps = VisitorFollowUp::query()
                ->where('outcome', FollowUpOutcome::Pending->value)
                ->where('is_scheduled', true)
                ->where('reminder_sent', false)
                ->where('scheduled_at', '<=', now()->addHours($hours))
                ->where('scheduled_at', '>=', now())
                ->with(['visitor.assignedMember.user', 'visitor.branch'])
                ->get();

            foreach ($followUps as $followUp) {
                $assignedMember = $followUp->visitor->assignedMember;

                if ($assignedMember && $assignedMember->user) {
                    $assignedMember->user->notify(new VisitorFollowUpReminderNotification($followUp));

                    $followUp->update(['reminder_sent' => true]);
                    $totalSent++;

                    $this->line("  - Sent reminder for {$followUp->visitor->fullName()} to {$assignedMember->user->email}");
                }
            }

            tenancy()->end();
        });

        $this->info("Done! Sent {$totalSent} reminder(s).");

        return Command::SUCCESS;
    }
}
