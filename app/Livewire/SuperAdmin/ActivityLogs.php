<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdminActivityLog;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogs extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = SuperAdminActivityLog::query()
            ->with(['superAdmin', 'tenant'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhereHas('superAdmin', function ($q) {
                            $q->where('name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->action, function ($query) {
                $query->where('action', $this->action);
            })
            ->latest('created_at');

        $actions = SuperAdminActivityLog::distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('livewire.super-admin.activity-logs', [
            'logs' => $query->paginate(25),
            'actions' => $actions,
        ])->layout('components.layouts.superadmin.app');
    }
}
