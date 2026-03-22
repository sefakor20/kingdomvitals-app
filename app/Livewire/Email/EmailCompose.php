<?php

declare(strict_types=1);

namespace App\Livewire\Email;

use App\Enums\EmailType;
use App\Jobs\SendBulkEmailJob;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\EmailLog;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Member;
use App\Services\BulkEmailService;
use App\Services\PlanAccessService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EmailCompose extends Component
{
    public Branch $branch;

    // Recipient selection
    public string $recipientType = 'individual'; // individual, cluster, all_members

    /** @var array<string> */
    public array $selectedMemberIds = [];

    public ?string $selectedClusterId = null;

    // Message composition
    public string $subject = '';

    public string $body = '';

    public ?string $templateId = null;

    public string $messageType = 'custom';

    // Preview modal
    public bool $showPreviewModal = false;

    /** @var array<array{id: string, name: string, email: string}> */
    public array $previewRecipients = [];

    // Confirmation modal
    public bool $showConfirmModal = false;

    // Success modal
    public bool $showSuccessModal = false;

    public int $sentCount = 0;

    // Opted-out recipients warning
    public int $optedOutCount = 0;

    public bool $showOptedOutWarningModal = false;

    public bool $acknowledgedOptedOutWarning = false;

    public function mount(Branch $branch): void
    {
        $this->authorize('create', [EmailLog::class, $branch]);
        $this->branch = $branch;
    }

    #[Computed]
    public function members(): Collection
    {
        return Member::where('primary_branch_id', $this->branch->id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function clusters(): Collection
    {
        return Cluster::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->withCount(['members' => function ($query): void {
                $query->whereNotNull('email')->where('email', '!=', '');
            }])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function templates(): Collection
    {
        return EmailTemplate::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function emailTypes(): array
    {
        return EmailType::cases();
    }

    /**
     * Get email quota information for display.
     *
     * @return array{sent: int, max: int|null, unlimited: bool, remaining: int|null, percent: float}
     */
    #[Computed]
    public function emailQuota(): array
    {
        return app(PlanAccessService::class)->getEmailQuota();
    }

    /**
     * Check if the quota warning should be shown (above 80% usage).
     */
    #[Computed]
    public function showQuotaWarning(): bool
    {
        return app(PlanAccessService::class)->isQuotaWarning('email', 80);
    }

    /**
     * Check if email can be sent based on quota and recipient count.
     */
    #[Computed]
    public function canSendWithinQuota(): bool
    {
        return app(PlanAccessService::class)->canSendEmail($this->recipientCount);
    }

    /**
     * Get available placeholders for message composition.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function availablePlaceholders(): array
    {
        return BulkEmailService::getAvailablePlaceholders();
    }

    /**
     * Get the Markdown formatting guide.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function markdownGuide(): array
    {
        return BulkEmailService::getMarkdownGuide();
    }

    /**
     * Get the body content rendered as HTML from Markdown.
     */
    #[Computed]
    public function bodyPreview(): string
    {
        if (empty($this->body)) {
            return '';
        }

        return BulkEmailService::markdownToHtml($this->body);
    }

    #[Computed]
    public function recipientCount(): int
    {
        return count($this->getRecipients());
    }

    public function updatedTemplateId(): void
    {
        if ($this->templateId) {
            $template = EmailTemplate::find($this->templateId);
            if ($template) {
                $this->subject = $template->subject;
                $this->body = $template->body;
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
        $emailTypes = collect(EmailType::cases())->pluck('value')->implode(',');

        return [
            'subject' => ['required', 'string', 'min:1', 'max:255'],
            'body' => ['required', 'string', 'min:1', 'max:65000'],
            'messageType' => ['required', 'string', 'in:'.$emailTypes],
        ];
    }

    protected function getRecipients(): array
    {
        $recipients = [];

        if ($this->recipientType === 'individual') {
            $members = Member::whereIn('id', $this->selectedMemberIds)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            foreach ($members as $member) {
                $recipients[] = [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'email' => $member->email,
                    'email_opt_out' => $member->email_opt_out,
                ];
            }
        } elseif ($this->recipientType === 'cluster' && $this->selectedClusterId) {
            $cluster = Cluster::find($this->selectedClusterId);
            if ($cluster) {
                $members = $cluster->members()
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->get();

                foreach ($members as $member) {
                    $recipients[] = [
                        'id' => $member->id,
                        'name' => $member->fullName(),
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'email' => $member->email,
                        'email_opt_out' => $member->email_opt_out,
                    ];
                }
            }
        } elseif ($this->recipientType === 'all_members') {
            $members = Member::where('primary_branch_id', $this->branch->id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            foreach ($members as $member) {
                $recipients[] = [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'email' => $member->email,
                    'email_opt_out' => $member->email_opt_out,
                ];
            }
        }

        return $recipients;
    }

    public function preview(): void
    {
        $this->validate();

        $recipients = $this->getRecipients();

        if ($recipients === []) {
            $this->addError('recipients', 'No recipients selected or all selected members have no email address.');

            return;
        }

        // Check for opted-out recipients
        $this->optedOutCount = collect($recipients)->where('email_opt_out', true)->count();

        // If there are opted-out recipients and user hasn't acknowledged, show warning
        if ($this->optedOutCount > 0 && ! $this->acknowledgedOptedOutWarning) {
            $this->previewRecipients = $recipients;
            $this->showOptedOutWarningModal = true;

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

    public function acknowledgeOptedOutWarning(): void
    {
        $this->acknowledgedOptedOutWarning = true;
        $this->showOptedOutWarningModal = false;
        $this->showPreviewModal = true;
    }

    public function cancelOptedOutWarning(): void
    {
        $this->showOptedOutWarningModal = false;
        $this->previewRecipients = [];
        $this->optedOutCount = 0;
    }

    public function send(): void
    {
        $this->authorize('create', [EmailLog::class, $this->branch]);

        $this->validate();

        $recipients = $this->getRecipients();

        if ($recipients === []) {
            $this->addError('recipients', 'No recipients to send to.');

            return;
        }

        // Check email quota before sending
        $recipientCount = count($recipients);
        if (! app(PlanAccessService::class)->canSendEmail($recipientCount)) {
            $quota = $this->emailQuota;
            $this->addError('quota', __('Insufficient email credits. You need :count but have :remaining remaining this month.', [
                'count' => $recipientCount,
                'remaining' => $quota['remaining'] ?? 0,
            ]));

            return;
        }

        // Create email logs via service
        $service = BulkEmailService::forBranch($this->branch);
        $emailLogs = $service->createLogs(
            $recipients,
            $this->subject,
            $this->body,
            EmailType::from($this->messageType),
            auth()->id()
        );

        // Dispatch bulk email job
        if ($emailLogs->isNotEmpty()) {
            SendBulkEmailJob::dispatch($emailLogs->pluck('id')->toArray());
        }

        // Invalidate email count cache for quota tracking
        app(PlanAccessService::class)->invalidateCountCache('email');

        $this->sentCount = count($recipients);
        $this->showConfirmModal = false;
        $this->showSuccessModal = true;
    }

    public function closeSuccess(): void
    {
        $this->showSuccessModal = false;

        // Redirect to email index
        $this->redirect(route('email.index', $this->branch), navigate: true);
    }

    public function resetForm(): void
    {
        $this->reset([
            'recipientType', 'selectedMemberIds', 'selectedClusterId',
            'subject', 'body', 'templateId', 'messageType',
            'optedOutCount', 'showOptedOutWarningModal', 'acknowledgedOptedOutWarning',
        ]);
        $this->resetValidation();

        unset($this->recipientCount);
    }

    public function render(): Factory|View
    {
        return view('livewire.email.email-compose');
    }
}
