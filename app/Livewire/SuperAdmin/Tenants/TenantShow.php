<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Tenants;

use App\Enums\TenantStatus;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class TenantShow extends Component
{
    public Tenant $tenant;

    public bool $showSuspendModal = false;

    public string $suspensionReason = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function suspend(): void
    {
        $this->validate([
            'suspensionReason' => ['required', 'string', 'max:500'],
        ]);

        $this->tenant->suspend($this->suspensionReason);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_suspended',
            description: "Suspended tenant: {$this->tenant->name}",
            tenant: $this->tenant,
            metadata: ['reason' => $this->suspensionReason],
        );

        $this->showSuspendModal = false;
        $this->suspensionReason = '';
        $this->tenant->refresh();
    }

    public function reactivate(): void
    {
        $this->tenant->reactivate();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_reactivated',
            description: "Reactivated tenant: {$this->tenant->name}",
            tenant: $this->tenant,
        );

        $this->tenant->refresh();
    }

    public function updateStatus(string $status): void
    {
        $newStatus = TenantStatus::from($status);
        $oldStatus = $this->tenant->status;

        $this->tenant->update(['status' => $newStatus]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_status_changed',
            description: "Changed tenant status from {$oldStatus->label()} to {$newStatus->label()}",
            tenant: $this->tenant,
            metadata: ['old_status' => $oldStatus->value, 'new_status' => $newStatus->value],
        );

        $this->tenant->refresh();
    }

    public function render(): View
    {
        return view('livewire.super-admin.tenants.tenant-show', [
            'statuses' => TenantStatus::cases(),
            'recentActivity' => SuperAdminActivityLog::where('tenant_id', $this->tenant->id)
                ->with('superAdmin')
                ->latest('created_at')
                ->take(10)
                ->get(),
        ])->layout('components.layouts.superadmin.app');
    }
}
