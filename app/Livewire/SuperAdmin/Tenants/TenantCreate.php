<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Tenants;

use App\Enums\TenantStatus;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class TenantCreate extends Component
{
    public string $name = '';

    public string $domain = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $address = '';

    public int $trial_days = 14;

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $tenantId = Str::slug($this->name).'-'.Str::random(6);

        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $this->name,
            'status' => TenantStatus::Trial,
            'contact_email' => $this->contact_email ?: null,
            'contact_phone' => $this->contact_phone ?: null,
            'address' => $this->address ?: null,
            'trial_ends_at' => now()->addDays($this->trial_days),
        ]);

        $tenant->domains()->create([
            'domain' => $this->domain,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_created',
            description: "Created new tenant: {$tenant->name}",
            tenant: $tenant,
            metadata: [
                'domain' => $this->domain,
                'trial_days' => $this->trial_days,
            ],
        );

        session()->flash('success', 'Tenant created successfully.');
        $this->redirect(route('superadmin.tenants.show', $tenant), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.super-admin.tenants.tenant-create')
            ->layout('components.layouts.superadmin.app');
    }
}
