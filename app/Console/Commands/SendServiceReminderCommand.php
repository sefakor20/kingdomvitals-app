<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendServiceReminderCommand extends Command
{
    protected $signature = 'sms:send-service-reminder {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send SMS reminders for upcoming services';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No SMS will actually be sent');
        }

        $this->info('Starting service reminder SMS job...');

        $totalSent = 0;
        $totalSkipped = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($dryRun, &$totalSent, &$totalSkipped): void {
            tenancy()->initialize($tenant);

            $this->line("Processing tenant: {$tenant->id}");

            Branch::all()->each(function (Branch $branch) use ($dryRun, &$totalSent, &$totalSkipped): void {
                // Check if branch has SMS configured
                if (! $branch->hasSmsConfigured()) {
                    $this->line("  Branch {$branch->name}: SMS not configured, skipping");

                    return;
                }

                // Check if service reminders are enabled
                if (! $branch->getSetting('auto_service_reminder', false)) {
                    $this->line("  Branch {$branch->name}: Service reminders disabled, skipping");

                    return;
                }

                $reminderHours = (int) $branch->getSetting('service_reminder_hours', 24);

                // Get active services for this branch that are upcoming within the reminder window
                $upcomingServices = $this->getUpcomingServices($branch, $reminderHours);

                if ($upcomingServices->isEmpty()) {
                    $this->line("  Branch {$branch->name}: No services within {$reminderHours}h window");

                    return;
                }

                $this->info("  Branch {$branch->name}: Found {$upcomingServices->count()} upcoming service(s)");

                // Get the TextTango service for this branch
                $service = TextTangoService::forBranch($branch);

                foreach ($upcomingServices as $serviceData) {
                    $churchService = $serviceData['service'];
                    $nextOccurrence = $serviceData['next_occurrence'];

                    $this->line("    Service: {$churchService->name} at {$nextOccurrence->format('D M j, g:i A')}");

                    // Get recipients based on branch setting
                    $recipients = $this->getRecipients($branch, $churchService);

                    if ($recipients->isEmpty()) {
                        $this->line('      No recipients found');

                        continue;
                    }

                    $this->line("      Found {$recipients->count()} recipient(s)");

                    // Get reminder message
                    $message = $this->getReminderMessage($branch, $churchService, $nextOccurrence);

                    foreach ($recipients as $member) {
                        // Check if reminder already sent today for this service
                        $alreadySent = SmsLog::where('member_id', $member->id)
                            ->where('message_type', SmsType::Reminder)
                            ->whereDate('created_at', now()->toDateString())
                            ->where('message', 'like', "%{$churchService->name}%")
                            ->exists();

                        if ($alreadySent) {
                            $this->line("      - {$member->fullName()}: Already reminded today, skipping");
                            $totalSkipped++;

                            continue;
                        }

                        // Personalize message
                        $personalizedMessage = $this->personalizeMessage($message, $member, $churchService, $nextOccurrence, $branch);

                        if ($dryRun) {
                            $this->line("      - Would send to {$member->fullName()} ({$member->phone})");
                            $totalSent++;

                            continue;
                        }

                        // Send SMS
                        $result = $service->sendBulkSms([$member->phone], $personalizedMessage);

                        // Log the SMS
                        SmsLog::create([
                            'branch_id' => $branch->id,
                            'member_id' => $member->id,
                            'phone_number' => $member->phone,
                            'message' => $personalizedMessage,
                            'message_type' => SmsType::Reminder,
                            'status' => $result['success'] ? SmsStatus::Sent : SmsStatus::Failed,
                            'provider' => 'texttango',
                            'provider_message_id' => $result['tracking_id'] ?? null,
                            'sent_at' => $result['success'] ? now() : null,
                            'error_message' => $result['error'] ?? null,
                        ]);

                        if ($result['success']) {
                            $this->line("      - Sent to {$member->fullName()} ({$member->phone})");
                            $totalSent++;
                        } else {
                            $this->error("      - Failed to send to {$member->fullName()}: ".($result['error'] ?? 'Unknown error'));
                            Log::error('Service reminder SMS failed', [
                                'member_id' => $member->id,
                                'branch_id' => $branch->id,
                                'service_id' => $churchService->id,
                                'error' => $result['error'] ?? 'Unknown error',
                            ]);
                        }
                    }
                }
            });

            tenancy()->end();
        });

        $this->newLine();
        $this->info("Done! Sent {$totalSent} reminder SMS(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }

    /**
     * Get services occurring within the reminder window
     */
    protected function getUpcomingServices(Branch $branch, int $reminderHours): Collection
    {
        $now = now();
        $windowEnd = now()->addHours($reminderHours);

        return Service::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get()
            ->map(function (Service $service) use ($now, $windowEnd): ?array {
                $nextOccurrence = $this->calculateNextOccurrence($service);

                // Check if next occurrence is within the reminder window
                if ($nextOccurrence->between($now, $windowEnd)) {
                    return [
                        'service' => $service,
                        'next_occurrence' => $nextOccurrence,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Calculate the next occurrence of a service based on day_of_week and time
     */
    protected function calculateNextOccurrence(Service $service): Carbon
    {
        $now = now();
        $today = $now->dayOfWeek;
        $serviceDay = $service->day_of_week;

        // Calculate days until next occurrence
        $daysUntil = ($serviceDay - $today + 7) % 7;

        // If service is today, check if time has passed
        if ($daysUntil === 0) {
            $serviceDateTime = $now->copy()->setTimeFromTimeString($service->time);
            if ($serviceDateTime->isPast()) {
                $daysUntil = 7; // Next week
            }
        }

        return $now->copy()
            ->addDays($daysUntil)
            ->setTimeFromTimeString($service->time);
    }

    /**
     * Get recipients based on branch setting
     */
    protected function getRecipients(Branch $branch, Service $service): Collection
    {
        $recipientType = $branch->getSetting('service_reminder_recipients', 'all');

        $query = Member::where('primary_branch_id', $branch->id)
            ->whereNotNull('phone')
            ->where('status', MembershipStatus::Active)
            ->where('sms_opt_out', false);

        if ($recipientType === 'attendees') {
            // Only members who have attended this service before
            $attendedMemberIds = Attendance::where('service_id', $service->id)
                ->whereNotNull('member_id')
                ->distinct()
                ->pluck('member_id');

            $query->whereIn('id', $attendedMemberIds);
        }

        return $query->get();
    }

    /**
     * Get the reminder message template
     */
    protected function getReminderMessage(Branch $branch, Service $service, Carbon $nextOccurrence): string
    {
        // Check if branch has a specific reminder template configured
        $templateId = $branch->getSetting('service_reminder_template_id');

        if ($templateId) {
            $template = SmsTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template->body;
            }
        }

        // Try to find any active reminder template for this branch
        $template = SmsTemplate::where('branch_id', $branch->id)
            ->where('type', SmsType::Reminder)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->body;
        }

        // Default reminder message based on timing
        $hoursUntil = now()->diffInHours($nextOccurrence, false);
        if ($hoursUntil <= 6) {
            return 'Hi {first_name}, reminder: {service_name} starts in a few hours at {service_time}. See you soon!';
        }

        if ($nextOccurrence->isToday()) {
            return 'Hi {first_name}, reminder: {service_name} is today at {service_time}. We look forward to seeing you!';
        }

        return 'Hi {first_name}, reminder: {service_name} is tomorrow ({service_day}) at {service_time}. We look forward to seeing you!';
    }

    /**
     * Personalize the message with member and service details
     */
    protected function personalizeMessage(
        string $message,
        Member $member,
        Service $service,
        Carbon $nextOccurrence,
        Branch $branch
    ): string {
        $replacements = [
            '{first_name}' => $member->first_name,
            '{last_name}' => $member->last_name,
            '{full_name}' => $member->fullName(),
            '{service_name}' => $service->name,
            '{service_time}' => $nextOccurrence->format('g:i A'),
            '{service_day}' => $nextOccurrence->format('l'),
            '{branch_name}' => $branch->name,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }
}
