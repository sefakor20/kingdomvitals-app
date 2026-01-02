<?php

declare(strict_types=1);

namespace App\Livewire\Sms;

use App\Enums\SmsStatus;
use App\Enums\SmsType;
use App\Jobs\SendBulkSmsJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Member;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Services\TextTangoService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SmsCompose extends Component
{
    public Branch $branch;

    // Recipient selection
    public string $recipientType = 'individual'; // individual, cluster, all_members

    /** @var array<string> */
    public array $selectedMemberIds = [];

    public ?string $selectedClusterId = null;

    // Message composition
    public string $message = '';

    public ?string $templateId = null;

    public string $messageType = 'custom';

    // Scheduling
    public bool $isScheduled = false;

    public ?string $scheduledAt = null;

    // Preview modal
    public bool $showPreviewModal = false;

    /** @var array<array{id: string, name: string, phone: string}> */
    public array $previewRecipients = [];

    // Confirmation modal
    public bool $showConfirmModal = false;

    // Success modal
    public bool $showSuccessModal = false;

    public int $sentCount = 0;

    public function mount(Branch $branch): void
    {
        $this->authorize('create', [SmsLog::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function clusters(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->withCount(['members' => function ($query) {
                $query->whereNotNull('phone')->where('phone', '!=', '');
            }])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function templates(): Collection
    {
        return SmsTemplate::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function smsTypes(): array
    {
        return SmsType::cases();
    }

    #[Computed]
    public function accountBalance(): array
    {
        $service = TextTangoService::forBranch($this->branch);

        if (! $service->isConfigured()) {
            return ['success' => false, 'error' => 'SMS service not configured. Please configure SMS settings in branch settings.'];
        }

        return $service->getBalance();
    }

    #[Computed]
    public function isSmsConfigured(): bool
    {
        return TextTangoService::forBranch($this->branch)->isConfigured();
    }

    #[Computed]
    public function characterCount(): int
    {
        return strlen($this->message);
    }

    #[Computed]
    public function smsPartCount(): int
    {
        $length = strlen($this->message);
        if ($length === 0) {
            return 0;
        }

        // GSM 7-bit encoding: 160 chars per SMS, or 153 if multipart
        // Unicode: 70 chars per SMS, or 67 if multipart
        $isUnicode = preg_match('/[^\x00-\x7F]/', $this->message);

        if ($isUnicode) {
            return $length <= 70 ? 1 : (int) ceil($length / 67);
        }

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    #[Computed]
    public function recipientCount(): int
    {
        return count($this->getRecipients());
    }

    public function updatedTemplateId(): void
    {
        if ($this->templateId) {
            $template = SmsTemplate::find($this->templateId);
            if ($template) {
                $this->message = $template->body;
                $this->messageType = $template->type?->value ?? 'custom';
            }
        }
    }

    public function updatedRecipientType(): void
    {
        // Reset selections when changing recipient type
        $this->selectedMemberIds = [];
        $this->selectedClusterId = null;

        unset($this->recipientCount);
    }

    public function updatedSelectedMemberIds(): void
    {
        unset($this->recipientCount);
    }

    public function updatedSelectedClusterId(): void
    {
        unset($this->recipientCount);
    }

    protected function rules(): array
    {
        $smsTypes = collect(SmsType::cases())->pluck('value')->implode(',');

        return [
            'message' => ['required', 'string', 'min:1', 'max:1600'],
            'messageType' => ['required', 'string', 'in:'.$smsTypes],
            'isScheduled' => ['boolean'],
            'scheduledAt' => ['required_if:isScheduled,true', 'nullable', 'date', 'after:now'],
        ];
    }

    protected function getRecipients(): array
    {
        $recipients = [];

        if ($this->recipientType === 'individual') {
            $members = Member::whereIn('id', $this->selectedMemberIds)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get();

            foreach ($members as $member) {
                $recipients[] = [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'phone' => $member->phone,
                ];
            }
        } elseif ($this->recipientType === 'cluster' && $this->selectedClusterId) {
            $cluster = Cluster::find($this->selectedClusterId);
            if ($cluster) {
                $members = $cluster->members()
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '')
                    ->get();

                foreach ($members as $member) {
                    $recipients[] = [
                        'id' => $member->id,
                        'name' => $member->fullName(),
                        'phone' => $member->phone,
                    ];
                }
            }
        } elseif ($this->recipientType === 'all_members') {
            $members = Member::where('primary_branch_id', $this->branch->id)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get();

            foreach ($members as $member) {
                $recipients[] = [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'phone' => $member->phone,
                ];
            }
        }

        return $recipients;
    }

    public function preview(): void
    {
        $this->validate();

        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->addError('recipients', 'No recipients selected or all selected members have no phone number.');

            return;
        }

        $this->previewRecipients = $recipients;
        $this->showPreviewModal = true;
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewRecipients = [];
    }

    public function confirmSend(): void
    {
        $this->showPreviewModal = false;
        $this->showConfirmModal = true;
    }

    public function cancelConfirm(): void
    {
        $this->showConfirmModal = false;
    }

    public function send(): void
    {
        $this->authorize('create', [SmsLog::class, $this->branch]);
        $this->validate();

        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->addError('recipients', 'No recipients to send to.');

            return;
        }

        // Create SmsLog entries and dispatch job
        $phoneNumbers = [];
        $smsLogIds = [];

        foreach ($recipients as $recipient) {
            $smsLog = SmsLog::create([
                'branch_id' => $this->branch->id,
                'member_id' => $recipient['id'],
                'phone_number' => $recipient['phone'],
                'message' => $this->message,
                'message_type' => $this->messageType,
                'status' => SmsStatus::Pending,
                'provider' => 'texttango',
                'sent_by' => auth()->id(),
            ]);

            $phoneNumbers[] = $recipient['phone'];
            $smsLogIds[] = $smsLog->id;
        }

        // Dispatch bulk SMS job
        SendBulkSmsJob::dispatch(
            $smsLogIds,
            $phoneNumbers,
            $this->message,
            $this->isScheduled ? $this->scheduledAt : null
        );

        $this->sentCount = count($recipients);
        $this->showConfirmModal = false;
        $this->showSuccessModal = true;
    }

    public function closeSuccess(): void
    {
        $this->showSuccessModal = false;

        // Redirect to SMS index
        $this->redirect(route('sms.index', $this->branch), navigate: true);
    }

    public function resetForm(): void
    {
        $this->reset([
            'recipientType', 'selectedMemberIds', 'selectedClusterId',
            'message', 'templateId', 'messageType',
            'isScheduled', 'scheduledAt',
        ]);
        $this->resetValidation();

        unset($this->recipientCount);
        unset($this->characterCount);
        unset($this->smsPartCount);
    }

    public function render()
    {
        return view('livewire.sms.sms-compose');
    }
}
