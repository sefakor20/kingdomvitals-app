<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Announcements;

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementStatus;
use App\Enums\AnnouncementTargetAudience;
use App\Enums\DeliveryStatus;
use App\Jobs\ProcessAnnouncementJob;
use App\Jobs\SendAnnouncementJob;
use App\Livewire\Concerns\HasReportExport;
use App\Models\Announcement;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementIndex extends Component
{
    use HasReportExport, WithPagination;

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showViewModal = false;

    public bool $showDeleteModal = false;

    // Filter properties
    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    // Form fields
    public string $title = '';

    public string $content = '';

    public string $targetAudience = 'all';

    public array $specificTenantIds = [];

    public string $priority = 'normal';

    public bool $scheduleForLater = false;

    public ?string $scheduledAt = null;

    // Edit/View/Delete state
    public ?string $editAnnouncementId = null;

    public ?string $viewAnnouncementId = null;

    public ?string $deleteAnnouncementId = null;

    public ?Announcement $viewingAnnouncement = null;

    // Permission flags
    public bool $canCreate = false;

    public bool $canSend = false;

    public function mount(): void
    {
        $admin = Auth::guard('superadmin')->user();
        $this->canCreate = $admin->role->canCreateAnnouncements();
        $this->canSend = $admin->role->canSendAnnouncements();
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->content = '';
        $this->targetAudience = 'all';
        $this->specificTenantIds = [];
        $this->priority = 'normal';
        $this->scheduleForLater = false;
        $this->scheduledAt = null;
        $this->editAnnouncementId = null;
        $this->resetValidation();
    }

    public function createAnnouncement(): void
    {
        $this->ensureCanCreate();

        $this->validate($this->validationRules());

        $announcement = Announcement::create([
            'super_admin_id' => Auth::guard('superadmin')->id(),
            'title' => $this->title,
            'content' => $this->content,
            'target_audience' => AnnouncementTargetAudience::from($this->targetAudience),
            'specific_tenant_ids' => $this->targetAudience === 'specific' ? $this->specificTenantIds : null,
            'priority' => AnnouncementPriority::from($this->priority),
            'status' => $this->scheduleForLater ? AnnouncementStatus::Scheduled : AnnouncementStatus::Draft,
            'scheduled_at' => $this->scheduleForLater && $this->scheduledAt ? $this->scheduledAt : null,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_created',
            description: "Created announcement: {$announcement->title}",
            metadata: [
                'announcement_id' => $announcement->id,
                'target_audience' => $this->targetAudience,
                'priority' => $this->priority,
                'scheduled' => $this->scheduleForLater,
            ],
        );

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('announcement-created');
    }

    public function openEditModal(string $announcementId): void
    {
        $this->ensureCanCreate();

        $announcement = Announcement::findOrFail($announcementId);

        if (! $announcement->canBeEdited()) {
            $this->dispatch('error', message: 'This announcement can no longer be edited.');

            return;
        }

        $this->editAnnouncementId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->targetAudience = $announcement->target_audience->value;
        $this->specificTenantIds = $announcement->specific_tenant_ids ?? [];
        $this->priority = $announcement->priority->value;
        $this->scheduleForLater = $announcement->isScheduled();
        $this->scheduledAt = $announcement->scheduled_at?->format('Y-m-d\TH:i');

        $this->showEditModal = true;
    }

    public function updateAnnouncement(): void
    {
        $this->ensureCanCreate();

        $announcement = Announcement::findOrFail($this->editAnnouncementId);

        if (! $announcement->canBeEdited()) {
            $this->dispatch('error', message: 'This announcement can no longer be edited.');

            return;
        }

        $this->validate($this->validationRules());

        $announcement->update([
            'title' => $this->title,
            'content' => $this->content,
            'target_audience' => AnnouncementTargetAudience::from($this->targetAudience),
            'specific_tenant_ids' => $this->targetAudience === 'specific' ? $this->specificTenantIds : null,
            'priority' => AnnouncementPriority::from($this->priority),
            'status' => $this->scheduleForLater ? AnnouncementStatus::Scheduled : AnnouncementStatus::Draft,
            'scheduled_at' => $this->scheduleForLater && $this->scheduledAt ? $this->scheduledAt : null,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_updated',
            description: "Updated announcement: {$announcement->title}",
            metadata: ['announcement_id' => $announcement->id],
        );

        $this->showEditModal = false;
        $this->resetForm();
        $this->dispatch('announcement-updated');
    }

    public function sendAnnouncement(string $announcementId): void
    {
        $this->ensureCanSend();

        $announcement = Announcement::findOrFail($announcementId);

        if (! $announcement->canBeSent()) {
            $this->dispatch('error', message: 'This announcement cannot be sent.');

            return;
        }

        ProcessAnnouncementJob::dispatch($announcement->id);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_sent',
            description: "Initiated sending announcement: {$announcement->title}",
            metadata: [
                'announcement_id' => $announcement->id,
                'target_audience' => $announcement->target_audience->value,
            ],
        );

        $this->dispatch('announcement-sending');
    }

    public function duplicateAnnouncement(string $announcementId): void
    {
        $this->ensureCanCreate();

        $original = Announcement::findOrFail($announcementId);

        $copy = Announcement::create([
            'super_admin_id' => Auth::guard('superadmin')->id(),
            'title' => $original->title.' (Copy)',
            'content' => $original->content,
            'target_audience' => $original->target_audience,
            'specific_tenant_ids' => $original->specific_tenant_ids,
            'priority' => $original->priority,
            'status' => AnnouncementStatus::Draft,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_duplicated',
            description: "Duplicated announcement: {$original->title}",
            metadata: [
                'original_id' => $original->id,
                'new_id' => $copy->id,
            ],
        );

        $this->dispatch('announcement-duplicated');
    }

    public function viewAnnouncement(string $announcementId): void
    {
        $this->viewAnnouncementId = $announcementId;
        $this->viewingAnnouncement = Announcement::with('recipients.tenant')->findOrFail($announcementId);
        $this->showViewModal = true;
    }

    public function confirmDelete(string $announcementId): void
    {
        $this->ensureCanCreate();

        $announcement = Announcement::findOrFail($announcementId);

        if (! $announcement->canBeDeleted()) {
            $this->dispatch('error', message: 'This announcement cannot be deleted while sending.');

            return;
        }

        $this->deleteAnnouncementId = $announcementId;
        $this->showDeleteModal = true;
    }

    public function deleteAnnouncement(): void
    {
        $this->ensureCanCreate();

        $announcement = Announcement::findOrFail($this->deleteAnnouncementId);

        if (! $announcement->canBeDeleted()) {
            $this->dispatch('error', message: 'This announcement cannot be deleted while sending.');

            return;
        }

        $title = $announcement->title;
        $announcement->delete();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_deleted',
            description: "Deleted announcement: {$title}",
            metadata: ['announcement_id' => $this->deleteAnnouncementId],
        );

        $this->showDeleteModal = false;
        $this->deleteAnnouncementId = null;
        $this->dispatch('announcement-deleted');
    }

    public function resendFailed(string $announcementId): void
    {
        $this->ensureCanSend();

        $announcement = Announcement::findOrFail($announcementId);

        if ($announcement->failed_count === 0) {
            $this->dispatch('error', message: 'No failed recipients to resend.');

            return;
        }

        $failedRecipients = $announcement->recipients()
            ->where('delivery_status', DeliveryStatus::Failed)
            ->get();

        foreach ($failedRecipients as $recipient) {
            $recipient->resetForRetry();
            $announcement->decrement('failed_count');

            SendAnnouncementJob::dispatch($announcement->id, $recipient->id)
                ->delay(now()->addSeconds(rand(1, 10)));
        }

        $announcement->update(['status' => AnnouncementStatus::Sending]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'announcement_resent',
            description: "Resent failed announcements: {$announcement->title}",
            metadata: [
                'announcement_id' => $announcement->id,
                'resent_count' => $failedRecipients->count(),
            ],
        );

        $this->dispatch('announcement-resending');
    }

    public function exportCsv(): StreamedResponse
    {
        $announcements = Announcement::with('superAdmin')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $announcements->map(fn (Announcement $a): array => [
            'title' => $a->title,
            'status' => $a->status->label(),
            'priority' => $a->priority->label(),
            'target_audience' => $a->target_audience->label(),
            'total_recipients' => $a->total_recipients,
            'successful' => $a->successful_count,
            'failed' => $a->failed_count,
            'created_by' => $a->superAdmin?->name ?? 'Unknown',
            'created_at' => $a->created_at->format('Y-m-d H:i'),
            'sent_at' => $a->sent_at?->format('Y-m-d H:i') ?? 'Not sent',
        ]);

        $headers = [
            'Title',
            'Status',
            'Priority',
            'Target Audience',
            'Total Recipients',
            'Successful',
            'Failed',
            'Created By',
            'Created At',
            'Sent At',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_announcements',
            description: 'Exported announcements to CSV',
            metadata: ['record_count' => $announcements->count()],
        );

        return $this->exportToCsv($data, $headers, 'announcements-'.now()->format('Y-m-d').'.csv');
    }

    protected function ensureCanCreate(): void
    {
        if (! $this->canCreate) {
            abort(403, 'You do not have permission to manage announcements.');
        }
    }

    protected function ensureCanSend(): void
    {
        if (! $this->canSend) {
            abort(403, 'You do not have permission to send announcements.');
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function validationRules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'targetAudience' => ['required', 'in:all,active,trial,suspended,specific'],
            'priority' => ['required', 'in:normal,important,urgent'],
        ];

        if ($this->targetAudience === 'specific') {
            $rules['specificTenantIds'] = ['required', 'array', 'min:1'];
        }

        if ($this->scheduleForLater) {
            $rules['scheduledAt'] = ['required', 'date', 'after:now'];
        }

        return $rules;
    }

    public function render(): View
    {
        $query = Announcement::with('superAdmin')
            ->orderBy('created_at', 'desc');

        if ($this->search !== '' && $this->search !== '0') {
            $query->where('title', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== '' && $this->statusFilter !== '0') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.super-admin.announcements.announcement-index', [
            'announcements' => $query->paginate(15),
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
            'statuses' => AnnouncementStatus::cases(),
            'priorities' => AnnouncementPriority::cases(),
            'audiences' => AnnouncementTargetAudience::cases(),
        ])->layout('components.layouts.superadmin.app');
    }
}
