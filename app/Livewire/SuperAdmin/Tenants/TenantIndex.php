<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Tenants;

use App\Enums\TenantStatus;
use App\Livewire\Concerns\HasReportExport;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Services\TenantCreationService;
use Illuminate\Support\Facades\Auth;
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

    public string $admin_name = '';

    public string $admin_email = '';

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
        $this->admin_name = '';
        $this->admin_email = '';
        $this->resetValidation();
    }

    public function createTenant(TenantCreationService $tenantCreationService): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => [
                'required',
                'string',
                'max:255',
                'unique:domains,domain',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$/',
            ],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
        ], [
            'domain.regex' => 'The domain must be a valid domain format (e.g., tenant.example.com).',
        ]);

        $tenant = $tenantCreationService->createTenantWithAdmin(
            tenantData: [
                'name' => $this->name,
                'domain' => $this->domain,
                'contact_email' => $this->contact_email ?: null,
                'contact_phone' => $this->contact_phone ?: null,
                'address' => $this->address ?: null,
                'trial_days' => $this->trial_days,
            ],
            adminData: [
                'name' => $this->admin_name,
                'email' => $this->admin_email,
            ],
        );

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_created',
            description: "Created new tenant: {$tenant->name}",
            tenant: $tenant,
            metadata: [
                'domain' => $this->domain,
                'trial_days' => $this->trial_days,
                'admin_email' => $this->admin_email,
            ],
        );

        $this->showCreateModal = false;
        $this->resetCreateForm();

        session()->flash('success', 'Tenant created successfully. An invitation email has been sent to the admin.');
        $this->redirect(route('superadmin.tenants.show', $tenant), navigate: true);
    }

    public function exportCsv(): StreamedResponse
    {
        $tenants = $this->getFilteredTenants()->get();

        $data = $tenants->map(fn (Tenant $tenant): array => [
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
            ->when($this->showDeleted, function ($query): void {
                $query->onlyTrashed();
            })
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('id', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status && ! $this->showDeleted, function ($query): void {
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
