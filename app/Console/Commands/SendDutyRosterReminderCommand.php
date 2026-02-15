<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DutyRosterStatus;
use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Notifications\DutyRosterReminderNotification;
use App\Services\TextTangoService;
use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendDutyRosterReminderCommand extends Command
{
    protected $signature = 'sms:send-duty-roster-reminder {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send reminders to duty roster assignees (preachers, liturgists, readers)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No messages will actually be sent');
        }

        $this->info('Starting duty roster reminder job...');

        $totalSent = 0;
        $totalSkipped = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($dryRun, &$totalSent, &$totalSkipped): void {
            tenancy()->initialize($tenant);

            $this->line("Processing tenant: {$tenant->id}");

            Branch::all()->each(function (Branch $branch) use ($dryRun, &$totalSent, &$totalSkipped): void {
                // Check if duty roster reminders are enabled
                if (! $branch->getSetting('auto_duty_roster_reminder', false)) {
                    $this->line("  Branch {$branch->name}: Duty roster reminders disabled, skipping");

                    return;
                }

                $reminderDays = (int) $branch->getSetting('duty_roster_reminder_days', 3);
                $channels = $branch->getSetting('duty_roster_reminder_channels', ['sms']);

                // Get upcoming duty rosters within the reminder window
                $upcomingRosters = $this->getUpcomingRosters($branch, $reminderDays);

                if ($upcomingRosters->isEmpty()) {
                    $this->line("  Branch {$branch->name}: No rosters within {$reminderDays} day window");

                    return;
                }

                $this->info("  Branch {$branch->name}: Found {$upcomingRosters->count()} upcoming roster(s)");

                foreach ($upcomingRosters as $roster) {
                    // Skip if reminder already sent
                    if ($roster->hasReminderBeenSent()) {
                        $this->line("    Roster {$roster->service_date->format('Y-m-d')}: Already reminded, skipping");
                        $totalSkipped++;

                        continue;
                    }

                    $this->processRoster($roster, $branch, $channels, $dryRun, $totalSent, $totalSkipped);
                }
            });

            tenancy()->end();
        });

        $this->newLine();
        $this->info("Done! Sent {$totalSent} reminder(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }

    protected function getUpcomingRosters(Branch $branch, int $reminderDays): Collection
    {
        $targetDate = now()->addDays($reminderDays)->endOfDay();
        $windowStart = now()->startOfDay();

        return DutyRoster::where('branch_id', $branch->id)
            ->where('is_published', true)
            ->whereIn('status', [DutyRosterStatus::Published, DutyRosterStatus::Scheduled])
            ->whereBetween('service_date', [$windowStart, $targetDate])
            ->whereNull('reminder_sent_at')
            ->with(['preacher', 'liturgist', 'scriptures.reader', 'service', 'branch'])
            ->get();
    }

    protected function processRoster(
        DutyRoster $roster,
        Branch $branch,
        array $channels,
        bool $dryRun,
        int &$totalSent,
        int &$totalSkipped
    ): void {
        $this->line("    Roster for {$roster->service_date->format('M j, Y')}:");

        $assignees = $this->collectAssignees($roster);

        if ($assignees->isEmpty()) {
            $this->line('      No assignees with contact info');

            return;
        }

        $smsService = null;
        if (in_array('sms', $channels) && $branch->hasSmsConfigured()) {
            $smsService = TextTangoService::forBranch($branch);
        }

        $sentCount = 0;

        foreach ($assignees as $assignee) {
            $member = $assignee['member'];
            $role = $assignee['role'];

            // Send email notification if channel enabled and member has email
            if (in_array('email', $channels) && $member->email) {
                $this->sendEmailReminder($member, $roster, $role, $dryRun, $sentCount);
            }

            // Send SMS if channel enabled and member has phone
            if (in_array('sms', $channels) && $smsService && $member->phone && ! $member->hasOptedOutOfSms()) {
                $this->sendSmsReminder($member, $roster, $role, $branch, $smsService, $dryRun, $sentCount);
            }
        }

        $totalSent += $sentCount;

        // Mark roster as reminded (only if not dry run and we sent at least one message)
        if (! $dryRun && $sentCount > 0) {
            $roster->markReminderSent();
        }
    }

    protected function collectAssignees(DutyRoster $roster): Collection
    {
        $assignees = collect();

        if ($roster->preacher_id && $roster->preacher) {
            $assignees->push(['member' => $roster->preacher, 'role' => 'preacher']);
        }

        if ($roster->liturgist_id && $roster->liturgist) {
            $assignees->push(['member' => $roster->liturgist, 'role' => 'liturgist']);
        }

        foreach ($roster->scriptures as $scripture) {
            if (!$scripture->reader_id) {
                continue;
            }
            if (!$scripture->reader) {
                continue;
            }
            // Avoid duplicates if someone is both liturgist and reader
            if ($assignees->contains(fn ($a): bool => $a['member']->id === $scripture->reader->id)) {
                continue;
            }
            $assignees->push(['member' => $scripture->reader, 'role' => 'reader']);
        }

        return $assignees;
    }

    protected function sendEmailReminder(Member $member, DutyRoster $roster, string $role, bool $dryRun, int &$sentCount): void
    {
        if ($dryRun) {
            $this->line("      - Would email {$member->fullName()} ({$member->email}) as {$role}");
            $sentCount++;

            return;
        }

        // Create an anonymous notifiable for the member
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $member->email);

        Notification::send($notifiable, new DutyRosterReminderNotification($roster, $role));
        $this->line("      - Emailed {$member->fullName()} as {$role}");
        $sentCount++;
    }

    protected function sendSmsReminder(
        Member $member,
        DutyRoster $roster,
        string $role,
        Branch $branch,
        TextTangoService $smsService,
        bool $dryRun,
        int &$sentCount
    ): void {
        $message = $this->getReminderMessage($branch, $roster, $member, $role);

        if ($dryRun) {
            $this->line("      - Would SMS {$member->fullName()} ({$member->phone}) as {$role}");
            $sentCount++;

            return;
        }

        $result = $smsService->sendBulkSms([$member->phone], $message);

        SmsLog::create([
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'phone_number' => $member->phone,
            'message' => $message,
            'message_type' => SmsType::DutyRosterReminder,
            'status' => $result['success'] ? SmsStatus::Sent : SmsStatus::Failed,
            'provider' => 'texttango',
            'provider_message_id' => $result['tracking_id'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['error'] ?? null,
        ]);

        if ($result['success']) {
            $this->line("      - SMS sent to {$member->fullName()} as {$role}");
            $sentCount++;
        } else {
            $this->error("      - SMS failed for {$member->fullName()}: ".($result['error'] ?? 'Unknown error'));
            Log::error('Duty roster reminder SMS failed', [
                'member_id' => $member->id,
                'branch_id' => $branch->id,
                'duty_roster_id' => $roster->id,
                'role' => $role,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    protected function getReminderMessage(Branch $branch, DutyRoster $roster, Member $member, string $role): string
    {
        $templateId = $branch->getSetting('duty_roster_reminder_template_id');

        if ($templateId) {
            $template = SmsTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $this->personalizeMessage($template->body, $member, $roster, $role, $branch);
            }
        }

        // Default message
        $defaultMessage = 'Hi {first_name}, reminder: You are assigned as {role} for the service on {service_date} at {branch_name}. Please prepare accordingly.';

        return $this->personalizeMessage($defaultMessage, $member, $roster, $role, $branch);
    }

    protected function personalizeMessage(string $message, Member $member, DutyRoster $roster, string $role, Branch $branch): string
    {
        $replacements = [
            '{first_name}' => $member->first_name,
            '{last_name}' => $member->last_name,
            '{full_name}' => $member->fullName(),
            '{role}' => ucfirst($role),
            '{service_date}' => $roster->service_date->format('l, M j'),
            '{theme}' => $roster->theme ?? '',
            '{branch_name}' => $branch->name,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
