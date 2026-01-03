<?php

declare(strict_types=1);

namespace App\Livewire\Branches;

use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class BranchSettings extends Component
{
    public Branch $branch;

    // SMS Credentials
    public string $smsApiKey = '';

    public string $smsSenderId = '';

    public bool $showApiKey = false;

    public bool $hasExistingApiKey = false;

    public ?string $testConnectionResult = null;

    public ?string $testConnectionStatus = null;

    // Auto Birthday SMS
    public bool $autoBirthdaySms = false;

    public ?string $birthdayTemplateId = null;

    // Auto Service Reminder SMS
    public bool $autoServiceReminder = false;

    public int $serviceReminderHours = 24;

    public ?string $serviceReminderTemplateId = null;

    public string $serviceReminderRecipients = 'all';

    // Auto Welcome SMS
    public bool $autoWelcomeSms = false;

    public ?string $welcomeTemplateId = null;

    // Auto Attendance Follow-up SMS
    public bool $autoAttendanceFollowup = false;

    public int $attendanceFollowupHours = 24;

    public string $attendanceFollowupRecipients = 'regular';

    public int $attendanceFollowupMinAttendance = 3;

    public ?string $attendanceFollowupTemplateId = null;

    public function mount(Branch $branch): void
    {
        $this->authorize('update', $branch);
        $this->branch = $branch;

        // Load existing settings
        $existingApiKey = $this->branch->getSetting('sms_api_key');
        $this->hasExistingApiKey = ! empty($existingApiKey);

        // If there's an existing API key, show masked version
        if ($this->hasExistingApiKey) {
            try {
                $decrypted = Crypt::decryptString($existingApiKey);
                $this->smsApiKey = str_repeat('•', max(0, strlen($decrypted) - 4)).substr($decrypted, -4);
            } catch (\Exception $e) {
                // If decryption fails, it might be stored unencrypted (legacy)
                $this->smsApiKey = str_repeat('•', max(0, strlen($existingApiKey) - 4)).substr($existingApiKey, -4);
            }
        }

        $this->smsSenderId = $this->branch->getSetting('sms_sender_id') ?? '';

        // Load auto SMS settings
        $this->autoBirthdaySms = (bool) $this->branch->getSetting('auto_birthday_sms', false);
        $this->birthdayTemplateId = $this->branch->getSetting('birthday_template_id');

        // Load service reminder settings
        $this->autoServiceReminder = (bool) $this->branch->getSetting('auto_service_reminder', false);
        $this->serviceReminderHours = (int) $this->branch->getSetting('service_reminder_hours', 24);
        $this->serviceReminderTemplateId = $this->branch->getSetting('service_reminder_template_id');
        $this->serviceReminderRecipients = $this->branch->getSetting('service_reminder_recipients', 'all');

        // Load welcome SMS settings
        $this->autoWelcomeSms = (bool) $this->branch->getSetting('auto_welcome_sms', false);
        $this->welcomeTemplateId = $this->branch->getSetting('welcome_template_id');

        // Load attendance follow-up settings
        $this->autoAttendanceFollowup = (bool) $this->branch->getSetting('auto_attendance_followup', false);
        $this->attendanceFollowupHours = (int) $this->branch->getSetting('attendance_followup_hours', 24);
        $this->attendanceFollowupRecipients = $this->branch->getSetting('attendance_followup_recipients', 'regular');
        $this->attendanceFollowupMinAttendance = (int) $this->branch->getSetting('attendance_followup_min_attendance', 3);
        $this->attendanceFollowupTemplateId = $this->branch->getSetting('attendance_followup_template_id');
    }

    #[Computed]
    public function birthdayTemplates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('type', SmsType::Birthday)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function reminderTemplates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('type', SmsType::Reminder)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function welcomeTemplates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('type', SmsType::Welcome)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function followupTemplates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('type', SmsType::FollowUp)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'smsSenderId' => ['required', 'string', 'max:11', 'regex:/^[a-zA-Z0-9]+$/'],
        ];
    }

    protected function messages(): array
    {
        return [
            'smsSenderId.required' => __('Sender ID is required.'),
            'smsSenderId.max' => __('Sender ID cannot exceed 11 characters.'),
            'smsSenderId.regex' => __('Sender ID can only contain letters and numbers.'),
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->branch);

        $this->validate();

        // Only update API key if a new one was entered (not the masked version)
        if ($this->smsApiKey && ! str_contains($this->smsApiKey, '•')) {
            $encryptedApiKey = Crypt::encryptString($this->smsApiKey);
            $this->branch->setSetting('sms_api_key', $encryptedApiKey);
        }

        $this->branch->setSetting('sms_sender_id', $this->smsSenderId);

        // Save auto SMS settings
        $this->branch->setSetting('auto_birthday_sms', $this->autoBirthdaySms);
        $this->branch->setSetting('birthday_template_id', $this->birthdayTemplateId);

        // Save service reminder settings
        $this->branch->setSetting('auto_service_reminder', $this->autoServiceReminder);
        $this->branch->setSetting('service_reminder_hours', $this->serviceReminderHours);
        $this->branch->setSetting('service_reminder_template_id', $this->serviceReminderTemplateId);
        $this->branch->setSetting('service_reminder_recipients', $this->serviceReminderRecipients);

        // Save welcome SMS settings
        $this->branch->setSetting('auto_welcome_sms', $this->autoWelcomeSms);
        $this->branch->setSetting('welcome_template_id', $this->welcomeTemplateId);

        // Save attendance follow-up settings
        $this->branch->setSetting('auto_attendance_followup', $this->autoAttendanceFollowup);
        $this->branch->setSetting('attendance_followup_hours', $this->attendanceFollowupHours);
        $this->branch->setSetting('attendance_followup_recipients', $this->attendanceFollowupRecipients);
        $this->branch->setSetting('attendance_followup_min_attendance', $this->attendanceFollowupMinAttendance);
        $this->branch->setSetting('attendance_followup_template_id', $this->attendanceFollowupTemplateId);

        $this->branch->save();

        $this->hasExistingApiKey = ! empty($this->branch->getSetting('sms_api_key'));

        // Mask the API key after saving
        if ($this->smsApiKey && ! str_contains($this->smsApiKey, '•')) {
            $this->smsApiKey = str_repeat('•', max(0, strlen($this->smsApiKey) - 4)).substr($this->smsApiKey, -4);
        }

        $this->dispatch('settings-saved');
    }

    public function testConnection(): void
    {
        $this->authorize('update', $this->branch);

        $this->testConnectionResult = null;
        $this->testConnectionStatus = null;

        // Get the API key to test with
        $apiKeyToTest = null;

        if ($this->smsApiKey && ! str_contains($this->smsApiKey, '•')) {
            // New API key entered, use it directly
            $apiKeyToTest = $this->smsApiKey;
        } else {
            // Use existing encrypted API key
            $existingApiKey = $this->branch->getSetting('sms_api_key');
            if ($existingApiKey) {
                try {
                    $apiKeyToTest = Crypt::decryptString($existingApiKey);
                } catch (\Exception $e) {
                    // Legacy unencrypted key
                    $apiKeyToTest = $existingApiKey;
                }
            }
        }

        if (empty($apiKeyToTest)) {
            $this->testConnectionResult = __('Please enter an API key first.');
            $this->testConnectionStatus = 'error';

            return;
        }

        $senderId = $this->smsSenderId ?: $this->branch->getSetting('sms_sender_id');

        if (empty($senderId)) {
            $this->testConnectionResult = __('Please enter a Sender ID first.');
            $this->testConnectionStatus = 'error';

            return;
        }

        // Test the connection by checking balance
        $service = new TextTangoService($apiKeyToTest, $senderId);
        $result = $service->getBalance();

        if ($result['success']) {
            $this->testConnectionResult = __('Connection successful! Balance: :currency :balance', [
                'currency' => $result['currency'] ?? 'GHS',
                'balance' => number_format($result['balance'] ?? 0, 2),
            ]);
            $this->testConnectionStatus = 'success';
        } else {
            $this->testConnectionResult = __('Connection failed: :error', [
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            $this->testConnectionStatus = 'error';
        }
    }

    public function clearApiKey(): void
    {
        $this->authorize('update', $this->branch);

        $this->smsApiKey = '';
        $this->hasExistingApiKey = false;
        $this->branch->setSetting('sms_api_key', null);
        $this->branch->save();

        $this->dispatch('api-key-cleared');
    }

    public function render()
    {
        return view('livewire.branches.branch-settings');
    }
}
