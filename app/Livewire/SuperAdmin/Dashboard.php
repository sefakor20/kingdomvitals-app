<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Enums\TenantStatus;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.super-admin.dashboard', [
            'totalTenants' => Tenant::count(),
            'activeTenants' => Tenant::where('status', TenantStatus::Active)->count(),
            'trialTenants' => Tenant::where('status', TenantStatus::Trial)->count(),
            'suspendedTenants' => Tenant::where('status', TenantStatus::Suspended)->count(),
            'recentActivity' => SuperAdminActivityLog::with('superAdmin')
                ->latest('created_at')
                ->take(10)
                ->get(),
            'recentTenants' => Tenant::latest()
                ->take(5)
                ->get(),
        ])->layout('components.layouts.superadmin.app');
    }
}
