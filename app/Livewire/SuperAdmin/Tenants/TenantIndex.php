<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Tenants;

use App\Enums\TenantStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantIndex extends Component
{
    use HasReportExport;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $showDeleted = false;

    public bool $showCreateModal = false;

    public string $name = '';

    public string $domain = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $address = '';

    public int $trial_days = 14;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeleted(): void
    {
        $this->resetPage();
    }

    public function resetCreateForm(): void
    {
        $this->name = '';
        $this->domain = '';
        $this->contact_email = '';
        $this->contact_phone = '';
        $this->address = '';
        $this->trial_days = 14;
        $this->resetValidation();
    }

    public function createTenant(): void
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

        $this->showCreateModal = false;
        $this->resetCreateForm();

        session()->flash('success', 'Tenant created successfully.');
        $this->redirect(route('superadmin.tenants.show', $tenant), navigate: true);
    }

    public function exportCsv(): StreamedResponse
    {
        $tenants = $this->getFilteredTenants()->get();

        $data = $tenants->map(fn (Tenant $tenant) => [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'status' => $tenant->status?->label() ?? 'Unknown',
            'contact_email' => $tenant->contact_email ?? '',
            'contact_phone' => $tenant->contact_phone ?? '',
            'subscription_plan' => $tenant->subscriptionPlan?->name ?? 'None',
            'created_at' => $tenant->created_at->format('Y-m-d H:i:s'),
        ]);

        $headers = [
            'ID',
            'Name',
            'Status',
            'Contact Email',
            'Contact Phone',
            'Subscription Plan',
            'Created At',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_tenants',
            description: 'Exported tenant list to CSV',
            metadata: [
                'record_count' => $tenants->count(),
                'filters' => [
                    'search' => $this->search,
                    'status' => $this->status,
                    'show_deleted' => $this->showDeleted,
                ],
            ],
        );

        $filename = 'tenants-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Tenant>
     */
    private function getFilteredTenants(): \Illuminate\Database\Eloquent\Builder
    {
        return Tenant::query()
            ->with('subscriptionPlan')
            ->when($this->showDeleted, function ($query) {
                $query->onlyTrashed();
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('id', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status && ! $this->showDeleted, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest();
    }

    public function render(): View
    {
        return view('livewire.super-admin.tenants.tenant-index', [
            'tenants' => $this->getFilteredTenants()->paginate(15),
            'statuses' => TenantStatus::cases(),
        ])->layout('components.layouts.superadmin.app');
    }
}
