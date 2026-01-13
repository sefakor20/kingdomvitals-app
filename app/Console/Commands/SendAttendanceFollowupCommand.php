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

class SendAttendanceFollowupCommand extends Command
{
    protected $signature = 'sms:send-attendance-followup {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send SMS follow-ups to members who missed a service';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No SMS will actually be sent');
        }

        $this->info('Starting attendance follow-up SMS job...');

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

                // Check if attendance follow-up is enabled
                if (! $branch->getSetting('auto_attendance_followup', false)) {
                    $this->line("  Branch {$branch->name}: Attendance follow-up disabled, skipping");

                    return;
                }

                $followupHours = (int) $branch->getSetting('attendance_followup_hours', 24);
                $recipientType = $branch->getSetting('attendance_followup_recipients', 'regular');
                $minAttendance = (int) $branch->getSetting('attendance_followup_min_attendance', 3);

                // Get services that occurred within the follow-up window
                $servicesToFollowup = $this->getServicesInFollowupWindow($branch, $followupHours);

                if ($servicesToFollowup->isEmpty()) {
                    $this->line("  Branch {$branch->name}: No services in follow-up window");

                    return;
                }

                $this->info("  Branch {$branch->name}: Found {$servicesToFollowup->count()} service(s) to follow up");

                // Get the TextTango service for this branch
                $service = TextTangoService::forBranch($branch);

                foreach ($servicesToFollowup as $serviceData) {
                    $churchService = $serviceData['service'];
                    $serviceDate = $serviceData['date'];

                    $this->line("    Service: {$churchService->name} on {$serviceDate->format('D M j')}");

                    // Get members who missed this service
                    $missedMembers = $this->getMissedMembers($branch, $churchService, $serviceDate, $recipientType, $minAttendance);

                    if ($missedMembers->isEmpty()) {
                        $this->line('      No members to follow up');

                        continue;
                    }

                    $this->line("      Found {$missedMembers->count()} member(s) to follow up");

                    // Get follow-up message
                    $message = $this->getFollowupMessage($branch, $churchService, $serviceDate);

                    foreach ($missedMembers as $member) {
                        // Check if follow-up already sent for this service occurrence
                        $alreadySent = SmsLog::where('member_id', $member->id)
                            ->where('message_type', SmsType::FollowUp)
                            ->where('message', 'like', "%{$churchService->name}%")
                            ->where('created_at', '>=', $serviceDate->copy()->startOfDay())
                            ->where('created_at', '<=', $serviceDate->copy()->addDays(7))
                            ->exists();

                        if ($alreadySent) {
                            $this->line("      - {$member->fullName()}: Already followed up, skipping");
                            $totalSkipped++;

                            continue;
                        }

                        // Personalize message
                        $personalizedMessage = $this->personalizeMessage($message, $member, $churchService, $serviceDate, $branch);

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
                            'message_type' => SmsType::FollowUp,
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
                            Log::error('Attendance follow-up SMS failed', [
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
        $this->info("Done! Sent {$totalSent} follow-up SMS(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }

    /**
     * Get services that occurred within the follow-up window
     */
    protected function getServicesInFollowupWindow(Branch $branch, int $followupHours): Collection
    {
        $now = now();

        return Service::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->get()
            ->map(function (Service $service) use ($now, $followupHours): ?array {
                // Calculate when this service last occurred
                $lastOccurrence = $this->calculateLastOccurrence($service);

                // Calculate hours since the service ended (assuming 2 hour service duration)
                $serviceEndTime = $lastOccurrence->copy()->addHours(2);

                // Only consider services that have already ended
                if ($serviceEndTime->isFuture()) {
                    return null;
                }

                $hoursSinceEnd = $serviceEndTime->diffInHours($now);

                // Check if within follow-up window:
                // - At least followupHours have passed since service ended
                // - But not more than followupHours + 24 hours (to prevent re-sending)
                if ($hoursSinceEnd >= $followupHours && $hoursSinceEnd < ($followupHours + 24)) {
                    return [
                        'service' => $service,
                        'date' => $lastOccurrence->toDateString() === $now->toDateString()
                            ? Carbon::parse($lastOccurrence->toDateString())
                            : Carbon::parse($lastOccurrence->toDateString()),
                    ];
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * Calculate when a service last occurred based on day_of_week and time
     */
    protected function calculateLastOccurrence(Service $service): Carbon
    {
        $now = now();
        $today = $now->dayOfWeek;
        $serviceDay = $service->day_of_week;

        // Calculate days since last occurrence
        $daysSince = ($today - $serviceDay + 7) % 7;

        // If service is today, check if time has passed
        if ($daysSince === 0) {
            $serviceDateTime = $now->copy()->setTimeFromTimeString($service->time);
            if ($serviceDateTime->isFuture()) {
                $daysSince = 7; // Last week
            }
        }

        return $now->copy()
            ->subDays($daysSince)
            ->setTimeFromTimeString($service->time);
    }

    /**
     * Get members who missed the service
     */
    protected function getMissedMembers(Branch $branch, Service $service, Carbon $serviceDate, string $recipientType, int $minAttendance): Collection
    {
        // Get IDs of members who attended this service on this date
        $attendeeIds = Attendance::where('service_id', $service->id)
            ->where('date', $serviceDate->toDateString())
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->toArray();

        // Get active members with phone numbers who didn't attend
        $query = Member::where('primary_branch_id', $branch->id)
            ->whereNotNull('phone')
            ->where('status', MembershipStatus::Active)
            ->where('sms_opt_out', false)
            ->whereNotIn('id', $attendeeIds);

        if ($recipientType === 'regular') {
            // Only members who have attended this service at least X times in last 2 months
            $twoMonthsAgo = now()->subMonths(2);

            $query->whereHas('attendance', function ($q) use ($service, $twoMonthsAgo): void {
                $q->where('service_id', $service->id)
                    ->where('date', '>=', $twoMonthsAgo->toDateString());
            }, '>=', $minAttendance);
        }

        return $query->get();
    }

    /**
     * Get the follow-up message template
     */
    protected function getFollowupMessage(Branch $branch, Service $service, Carbon $serviceDate): string
    {
        // Check if branch has a specific follow-up template configured
        $templateId = $branch->getSetting('attendance_followup_template_id');

        if ($templateId) {
            $template = SmsTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template->body;
            }
        }

        // Try to find any active follow-up template for this branch
        $template = SmsTemplate::where('branch_id', $branch->id)
            ->where('type', SmsType::FollowUp)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->body;
        }

        // Default follow-up message
        return 'Hi {first_name}, we missed you at {service_name} on {service_day}! Hope all is well. Looking forward to seeing you next time.';
    }

    /**
     * Personalize the message with member and service details
     */
    protected function personalizeMessage(
        string $message,
        Member $member,
        Service $service,
        Carbon $serviceDate,
        Branch $branch
    ): string {
        $replacements = [
            '{first_name}' => $member->first_name,
            '{last_name}' => $member->last_name,
            '{full_name}' => $member->fullName(),
            '{service_name}' => $service->name,
            '{service_day}' => $serviceDate->format('l'),
            '{branch_name}' => $branch->name,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }
}
