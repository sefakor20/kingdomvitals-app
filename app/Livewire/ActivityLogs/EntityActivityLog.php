<?php

declare(strict_types=1);

namespace App\Livewire\ActivityLogs;

use App\Enums\SubjectType;
use App\Models\Tenant\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class EntityActivityLog extends Component
{
    public string $entityId;

    public SubjectType $subjectType;

    public int $limit = 10;

    public function mount(Model $entity, SubjectType $subjectType): void
    {
        $this->entityId = $entity->getKey();
        $this->subjectType = $subjectType;
    }

    #[Computed]
    public function activities(): Collection
    {
        return ActivityLog::query()
            ->where('subject_type', $this->subjectType)
            ->where('subject_id', $this->entityId)
            ->with('user:id,name')
            ->latest()
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return ActivityLog::query()
            ->where('subject_type', $this->subjectType)
            ->where('subject_id', $this->entityId)
            ->count() > $this->limit;
    }

    public function loadMore(): void
    {
        $this->limit += 10;
    }

    public function render(): View
    {
        return view('livewire.activity-logs.entity-activity-log');
    }
}
