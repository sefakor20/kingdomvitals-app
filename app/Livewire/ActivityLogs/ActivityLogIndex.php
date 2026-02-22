<?php

declare(strict_types=1);

namespace App\Livewire\ActivityLogs;

use App\Enums\ActivityEvent;
use App\Enums\SubjectType;
use App\Livewire\Concerns\HasReportExport;
use App\Models\Tenant\ActivityLog;
use App\Models\Tenant\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
class ActivityLogIndex extends Component
{
    use HasReportExport;
    use WithPagination;

    public Branch $branch;

    #[Url]
    public string $search = '';

    #[Url]
    public string $subjectType = '';

    #[Url]
    public string $event = '';

    #[Url]
    public string $userId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(Branch $branch): void
    {
        $this->branch = $branch;

        $this->authorize('viewActivityLogs', $branch);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSubjectType(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<SubjectType>
     */
    #[Computed]
    public function subjectTypes(): array
    {
        return SubjectType::cases();
    }

    /**
     * @return array<ActivityEvent>
     */
    #[Computed]
    public function events(): array
    {
        return ActivityEvent::cases();
    }

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->whereHas('branchAccess', fn ($q) => $q->where('branch_id', $this->branch->id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'subjectType', 'event', 'userId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $logs = $this->getFilteredQuery()->limit(10000)->get();

        $data = $logs->map(fn (ActivityLog $log): array => [
            'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
            'user' => $log->user?->name ?? 'System',
            'entity_type' => $log->subject_type->label(),
            'entity_name' => $log->subject_name ?? '-',
            'event' => $log->event->label(),
            'description' => $log->formatted_description,
            'ip_address' => $log->ip_address ?? '-',
        ]);

        $headers = [
            'Timestamp',
            'User',
            'Entity Type',
            'Entity Name',
            'Event',
            'Description',
            'IP Address',
        ];

        return $this->exportToCsv(
            $data,
            $headers,
            $this->generateFilename('activity-logs')
        );
    }

    /**
     * @return Builder<ActivityLog>
     */
    private function getFilteredQuery(): Builder
    {
        return ActivityLog::query()
            ->where('branch_id', $this->branch->id)
            ->with('user:id,name')
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhere('subject_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->subjectType, fn (Builder $q) => $q->where('subject_type', $this->subjectType))
            ->when($this->event, fn (Builder $q) => $q->where('event', $this->event))
            ->when($this->userId, fn (Builder $q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest('created_at');
    }

    public function render(): View
    {
        return view('livewire.activity-logs.activity-log-index', [
            'logs' => $this->getFilteredQuery()->paginate(25),
        ]);
    }
}
