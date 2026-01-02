<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBirthdaySmsCommand extends Command
{
    protected $signature = 'sms:send-birthday {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send birthday SMS greetings to members whose birthday is today';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No SMS will actually be sent');
        }

        $this->info('Starting birthday SMS job...');

        $totalSent = 0;
        $totalSkipped = 0;

        Tenant::all()->each(function (Tenant $tenant) use ($dryRun, &$totalSent, &$totalSkipped) {
            tenancy()->initialize($tenant);

            $this->line("Processing tenant: {$tenant->id}");

            // Get all branches with auto birthday SMS enabled
            Branch::all()->each(function (Branch $branch) use ($dryRun, &$totalSent, &$totalSkipped) {
                // Check if branch has SMS configured
                if (! $branch->hasSmsConfigured()) {
                    $this->line("  Branch {$branch->name}: SMS not configured, skipping");

                    return;
                }

                // Check if auto birthday SMS is enabled
                if (! $branch->getSetting('auto_birthday_sms', false)) {
                    $this->line("  Branch {$branch->name}: Auto birthday SMS disabled, skipping");

                    return;
                }

                // Get members with birthday today
                $todayMonth = now()->format('m');
                $todayDay = now()->format('d');

                $members = Member::where('primary_branch_id', $branch->id)
                    ->whereNotNull('date_of_birth')
                    ->whereNotNull('phone')
                    ->whereMonth('date_of_birth', $todayMonth)
                    ->whereDay('date_of_birth', $todayDay)
                    ->where('status', 'active')
                    ->get();

                if ($members->isEmpty()) {
                    $this->line("  Branch {$branch->name}: No birthdays today");

                    return;
                }

                $this->info("  Branch {$branch->name}: Found {$members->count()} birthday(s)");

                // Get birthday template or default message
                $message = $this->getBirthdayMessage($branch);

                // Get the TextTango service for this branch
                $service = TextTangoService::forBranch($branch);

                foreach ($members as $member) {
                    // Check if SMS already sent today to prevent duplicates
                    $alreadySent = SmsLog::where('member_id', $member->id)
                        ->where('message_type', SmsType::Birthday)
                        ->whereDate('created_at', now()->toDateString())
                        ->exists();

                    if ($alreadySent) {
                        $this->line("    - {$member->fullName()}: Already sent today, skipping");
                        $totalSkipped++;

                        continue;
                    }

                    // Personalize message
                    $personalizedMessage = $this->personalizeMessage($message, $member);

                    if ($dryRun) {
                        $this->line("    - Would send to {$member->fullName()} ({$member->phone}): {$personalizedMessage}");
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
                        'message_type' => SmsType::Birthday,
                        'status' => $result['success'] ? SmsStatus::Sent : SmsStatus::Failed,
                        'provider' => 'texttango',
                        'provider_message_id' => $result['tracking_id'] ?? null,
                        'sent_at' => $result['success'] ? now() : null,
                        'error_message' => $result['error'] ?? null,
                    ]);

                    if ($result['success']) {
                        $this->line("    - Sent to {$member->fullName()} ({$member->phone})");
                        $totalSent++;
                    } else {
                        $this->error("    - Failed to send to {$member->fullName()}: ".($result['error'] ?? 'Unknown error'));
                        Log::error('Birthday SMS failed', [
                            'member_id' => $member->id,
                            'branch_id' => $branch->id,
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                    }
                }
            });

            tenancy()->end();
        });

        $this->newLine();
        $this->info("Done! Sent {$totalSent} birthday SMS(s), skipped {$totalSkipped}.");

        return Command::SUCCESS;
    }

    protected function getBirthdayMessage(Branch $branch): string
    {
        // Check if branch has a specific birthday template configured
        $templateId = $branch->getSetting('birthday_template_id');

        if ($templateId) {
            $template = SmsTemplate::where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template->body;
            }
        }

        // Try to find any active birthday template for this branch
        $template = SmsTemplate::where('branch_id', $branch->id)
            ->where('type', SmsType::Birthday)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template->body;
        }

        // Default birthday message
        return 'Happy Birthday, {first_name}! Wishing you a blessed and wonderful day filled with joy.';
    }

    protected function personalizeMessage(string $message, Member $member): string
    {
        $replacements = [
            '{first_name}' => $member->first_name,
            '{last_name}' => $member->last_name,
            '{full_name}' => $member->fullName(),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
    }
}
