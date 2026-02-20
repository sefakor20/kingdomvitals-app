<?php

declare(strict_types=1);

namespace App\Livewire\Branches;

use App\Enums\Currency;
use App\Enums\SmsType;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SmsTemplate;
use App\Services\ImageProcessingService;
use App\Services\PaystackService;
use App\Services\TextTangoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class BranchSettings extends Component
{
    use WithFileUploads;

    public Branch $branch;

    // Tab State
    public string $activeTab = 'organization';

    // Organization Settings (tenant-level)
    public TemporaryUploadedFile|string|null $logo = null;

    public ?string $existingLogoUrl = null;

    public string $currency = 'GHS';

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

    // Auto Duty Roster Reminder
    public bool $autoDutyRosterReminder = false;

    public int $dutyRosterReminderDays = 3;

    /** @var array<int, string> */
    public array $dutyRosterReminderChannels = ['sms'];

    public ?string $dutyRosterReminderTemplateId = null;

    // Paystack Credentials
    public string $paystackPublicKey = '';

    public string $paystackSecretKey = '';

    public bool $paystackTestMode = true;

    public bool $hasExistingPaystackKeys = false;

    public bool $showPaystackSecretKey = false;

    public ?string $paystackTestConnectionResult = null;

    public ?string $paystackTestConnectionStatus = null;

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

        // Load duty roster reminder settings
        $this->autoDutyRosterReminder = (bool) $this->branch->getSetting('auto_duty_roster_reminder', false);
        $this->dutyRosterReminderDays = (int) $this->branch->getSetting('duty_roster_reminder_days', 3);
        $this->dutyRosterReminderChannels = $this->branch->getSetting('duty_roster_reminder_channels', ['sms']);
        $this->dutyRosterReminderTemplateId = $this->branch->getSetting('duty_roster_reminder_template_id');

        // Load Paystack settings
        $this->loadPaystackSettings();

        // Load organization settings from tenant
        $this->loadOrganizationSettings();
    }

    protected function loadOrganizationSettings(): void
    {
        $tenant = tenant();

        if ($tenant?->hasLogo()) {
            $this->existingLogoUrl = $tenant->getLogoUrl('medium');
        }

        $this->currency = $tenant?->getCurrencyCode() ?? 'GHS';
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function loadPaystackSettings(): void
    {
        $existingPublicKey = $this->branch->getSetting('paystack_public_key');
        $existingSecretKey = $this->branch->getSetting('paystack_secret_key');
        $this->hasExistingPaystackKeys = ! empty($existingPublicKey) && ! empty($existingSecretKey);

        if (! empty($existingPublicKey)) {
            try {
                $decrypted = Crypt::decryptString($existingPublicKey);
                $this->paystackPublicKey = str_repeat('•', max(0, strlen($decrypted) - 8)).substr($decrypted, -8);
            } catch (\Exception $e) {
                $this->paystackPublicKey = str_repeat('•', max(0, strlen($existingPublicKey) - 8)).substr($existingPublicKey, -8);
            }
        }

        if (! empty($existingSecretKey)) {
            try {
                $decrypted = Crypt::decryptString($existingSecretKey);
                $this->paystackSecretKey = str_repeat('•', max(0, strlen($decrypted) - 8)).substr($decrypted, -8);
            } catch (\Exception $e) {
                $this->paystackSecretKey = str_repeat('•', max(0, strlen($existingSecretKey) - 8)).substr($existingSecretKey, -8);
            }
        }

        $this->paystackTestMode = (bool) $this->branch->getSetting('paystack_test_mode', true);
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

    #[Computed]
    public function dutyRosterReminderTemplates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('type', SmsType::DutyRosterReminder)
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

        // Save duty roster reminder settings
        $this->branch->setSetting('auto_duty_roster_reminder', $this->autoDutyRosterReminder);
        $this->branch->setSetting('duty_roster_reminder_days', $this->dutyRosterReminderDays);
        $this->branch->setSetting('duty_roster_reminder_channels', $this->dutyRosterReminderChannels);
        $this->branch->setSetting('duty_roster_reminder_template_id', $this->dutyRosterReminderTemplateId);

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

    // Paystack Methods

    public function savePaystackSettings(): void
    {
        $this->authorize('update', $this->branch);

        // Only update keys if new ones were entered (not the masked version)
        if ($this->paystackPublicKey && ! str_contains($this->paystackPublicKey, '•')) {
            $encryptedKey = Crypt::encryptString($this->paystackPublicKey);
            $this->branch->setSetting('paystack_public_key', $encryptedKey);
        }

        if ($this->paystackSecretKey && ! str_contains($this->paystackSecretKey, '•')) {
            $encryptedKey = Crypt::encryptString($this->paystackSecretKey);
            $this->branch->setSetting('paystack_secret_key', $encryptedKey);
        }

        $this->branch->setSetting('paystack_test_mode', $this->paystackTestMode);
        $this->branch->save();

        $this->hasExistingPaystackKeys = $this->branch->hasPaystackConfigured();

        // Mask the keys after saving
        if ($this->paystackPublicKey && ! str_contains($this->paystackPublicKey, '•')) {
            $this->paystackPublicKey = str_repeat('•', max(0, strlen($this->paystackPublicKey) - 8)).substr($this->paystackPublicKey, -8);
        }

        if ($this->paystackSecretKey && ! str_contains($this->paystackSecretKey, '•')) {
            $this->paystackSecretKey = str_repeat('•', max(0, strlen($this->paystackSecretKey) - 8)).substr($this->paystackSecretKey, -8);
        }

        $this->dispatch('paystack-settings-saved');
    }

    public function testPaystackConnection(): void
    {
        $this->authorize('update', $this->branch);

        $this->paystackTestConnectionResult = null;
        $this->paystackTestConnectionStatus = null;

        // Get the keys to test with
        $publicKey = null;
        $secretKey = null;

        if ($this->paystackPublicKey && ! str_contains($this->paystackPublicKey, '•')) {
            $publicKey = $this->paystackPublicKey;
        } else {
            $existingKey = $this->branch->getSetting('paystack_public_key');
            if ($existingKey) {
                try {
                    $publicKey = Crypt::decryptString($existingKey);
                } catch (\Exception $e) {
                    $publicKey = $existingKey;
                }
            }
        }

        if ($this->paystackSecretKey && ! str_contains($this->paystackSecretKey, '•')) {
            $secretKey = $this->paystackSecretKey;
        } else {
            $existingKey = $this->branch->getSetting('paystack_secret_key');
            if ($existingKey) {
                try {
                    $secretKey = Crypt::decryptString($existingKey);
                } catch (\Exception $e) {
                    $secretKey = $existingKey;
                }
            }
        }

        if (empty($publicKey) || empty($secretKey)) {
            $this->paystackTestConnectionResult = __('Please enter both Public Key and Secret Key first.');
            $this->paystackTestConnectionStatus = 'error';

            return;
        }

        // Test the connection by initializing a small transaction (won't be charged)
        $service = new PaystackService($secretKey, $publicKey, $this->paystackTestMode);

        // Try to verify a non-existent reference - if we get "Transaction not found" it means the connection works
        $result = $service->verifyTransaction('test-connection-'.time());

        // A "Transaction reference not found" error means the API is working
        if (! $result['success'] && str_contains($result['error'] ?? '', 'not found')) {
            $this->paystackTestConnectionResult = __('Connection successful! Paystack API is working.');
            $this->paystackTestConnectionStatus = 'success';
        } elseif (! $result['success']) {
            // Check if it's an authentication error
            if (str_contains($result['error'] ?? '', 'Invalid key') || str_contains($result['error'] ?? '', 'Unauthorized')) {
                $this->paystackTestConnectionResult = __('Invalid API keys. Please check your credentials.');
            } else {
                $this->paystackTestConnectionResult = __('Connection test completed. Error: :error', [
                    'error' => $result['error'] ?? 'Unknown',
                ]);
            }
            $this->paystackTestConnectionStatus = 'error';
        } else {
            $this->paystackTestConnectionResult = __('Connection successful!');
            $this->paystackTestConnectionStatus = 'success';
        }
    }

    public function clearPaystackKeys(): void
    {
        $this->authorize('update', $this->branch);

        $this->paystackPublicKey = '';
        $this->paystackSecretKey = '';
        $this->hasExistingPaystackKeys = false;
        $this->branch->setSetting('paystack_public_key', null);
        $this->branch->setSetting('paystack_secret_key', null);
        $this->branch->save();

        $this->dispatch('paystack-keys-cleared');
    }

    #[Computed]
    public function givingUrl(): string
    {
        // return url("/branches/{$this->branch->id}/give");
        return route('giving.form', $this->branch->id);
    }

    // Organization Methods

    public function saveLogo(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        if (! $this->logo instanceof TemporaryUploadedFile) {
            return;
        }

        $imageService = app(ImageProcessingService::class);

        // Validate the logo
        $errors = $imageService->validateLogo($this->logo);
        if (! empty($errors)) {
            foreach ($errors as $message) {
                $this->addError('logo', $message);
            }

            return;
        }

        // Delete existing logo if present (from central storage)
        if ($tenant->hasLogo()) {
            $this->deleteLogoFromCentralStorage($tenant->logo);
        }

        // Process and store the new logo in central storage (bypasses tenant storage isolation)
        $paths = $this->processLogoToCentralStorage($this->logo, $tenant->id);

        // Save paths to tenant
        $tenant->setLogoPaths($paths);

        // Update URL for display
        $this->existingLogoUrl = $tenant->getLogoUrl('medium');
        $this->logo = null;

        $this->dispatch('logo-saved');
    }

    /**
     * Process logo and store in central storage (bypasses tenant storage isolation).
     *
     * @return array<string, string>
     */
    private function processLogoToCentralStorage(TemporaryUploadedFile $file, string $tenantId): array
    {
        $paths = [];
        $sizes = ImageProcessingService::LOGO_SIZES;

        // Use base_path to store in central storage, bypassing tenant storage prefix
        $directory = base_path("storage/app/public/logos/tenants/{$tenantId}");

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($sizes as $sizeName => $targetSize) {
            $resized = Image::read($file->getRealPath());
            $resized->cover($targetSize, $targetSize);

            $filename = "logo-{$sizeName}.png";
            $fullPath = $directory.'/'.$filename;

            $encoded = $resized->encode(new PngEncoder);
            file_put_contents($fullPath, (string) $encoded);

            // Store relative path for URL generation
            $paths[$sizeName] = "logos/tenants/{$tenantId}/{$filename}";
        }

        return $paths;
    }

    /**
     * Delete logo files from central storage.
     *
     * @param  array<string, string>  $paths
     */
    private function deleteLogoFromCentralStorage(array $paths): void
    {
        foreach ($paths as $sizeName => $relativePath) {
            $fullPath = base_path('storage/app/public/'.$relativePath);
            if (file_exists($fullPath) && ! unlink($fullPath)) {
                Log::warning('BranchSettings: Failed to delete logo file', [
                    'tenant_id' => tenant()?->id,
                    'size' => $sizeName,
                    'path' => $relativePath,
                ]);
            }
        }
    }

    public function removeLogo(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        $tenant->clearLogo();
        $this->existingLogoUrl = null;
        $this->logo = null;

        $this->dispatch('logo-removed');
    }

    public function saveCurrency(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403);
        }

        $currency = Currency::fromString($this->currency);
        $tenant->setCurrency($currency);

        $this->dispatch('currency-saved');
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.branches.branch-settings', [
            'currencies' => Currency::options(),
        ]);
    }
}
