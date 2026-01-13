<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Tenants;

use App\Enums\TenantStatus;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantAdminInvitationNotification;
use App\Services\TenantImpersonationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TenantShow extends Component
{
    public Tenant $tenant;

    public bool $showSuspendModal = false;

    public string $suspensionReason = '';

    public bool $showDeleteModal = false;

    public string $deleteConfirmation = '';

    public bool $showSubscriptionModal = false;

    public ?string $selectedPlanId = null;

    public bool $showAddDomainModal = false;

    public string $newDomain = '';

    public bool $showRemoveDomainModal = false;

    public ?string $domainToRemove = null;

    public bool $showImpersonateModal = false;

    public string $impersonationReason = '';

    public bool $showEditModal = false;

    public string $editName = '';

    public string $editContactEmail = '';

    public string $editContactPhone = '';

    public string $editAddress = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->selectedPlanId = $tenant->subscription_id;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    #[Computed]
    public function tenantUsers(): \Illuminate\Support\Collection
    {
        return $this->tenant->run(fn () => User::all());
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
        $this->dispatch('tenant-suspended');
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
        $this->dispatch('tenant-reactivated');
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
        $this->dispatch('tenant-status-updated');
    }

    public function delete(): void
    {
        $this->validate([
            'deleteConfirmation' => ['required', 'string', 'in:DELETE'],
        ], [
            'deleteConfirmation.in' => 'Please type DELETE to confirm.',
        ]);

        $tenantName = $this->tenant->name;

        $this->tenant->update(['status' => TenantStatus::Deleted]);
        $this->tenant->delete();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_deleted',
            description: "Deleted tenant: {$tenantName}",
            tenant: $this->tenant,
        );

        session()->flash('success', 'Tenant deleted successfully.');
        $this->redirect(route('superadmin.tenants.index'), navigate: true);
    }

    public function restore(): void
    {
        $this->tenant->restore();
        $this->tenant->update(['status' => TenantStatus::Inactive]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_restored',
            description: "Restored tenant: {$this->tenant->name}",
            tenant: $this->tenant,
        );

        $this->tenant->refresh();
        $this->dispatch('tenant-restored');
    }

    public function updateSubscription(): void
    {
        $this->validate([
            'selectedPlanId' => ['nullable', 'exists:subscription_plans,id'],
        ]);

        $oldPlan = $this->tenant->subscriptionPlan;
        $newPlan = $this->selectedPlanId
            ? SubscriptionPlan::find($this->selectedPlanId)
            : null;

        $this->tenant->update(['subscription_id' => $this->selectedPlanId]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_subscription_changed',
            description: sprintf(
                'Changed subscription from %s to %s',
                $oldPlan?->name ?? 'None',
                $newPlan?->name ?? 'None'
            ),
            tenant: $this->tenant,
            metadata: [
                'old_plan_id' => $oldPlan?->id,
                'old_plan_name' => $oldPlan?->name,
                'new_plan_id' => $newPlan?->id,
                'new_plan_name' => $newPlan?->name,
            ],
        );

        $this->showSubscriptionModal = false;
        $this->tenant->refresh();
        $this->dispatch('subscription-updated');
    }

    public function addDomain(): void
    {
        $this->validate([
            'newDomain' => [
                'required',
                'string',
                'max:255',
                'unique:domains,domain',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$/i',
            ],
        ], [
            'newDomain.unique' => 'This domain is already in use.',
            'newDomain.regex' => 'Please enter a valid domain format.',
        ]);

        $domain = strtolower($this->newDomain);

        $this->tenant->domains()->create([
            'domain' => $domain,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_domain_added',
            description: "Added domain {$domain} to tenant",
            tenant: $this->tenant,
            metadata: ['domain' => $domain],
        );

        $this->newDomain = '';
        $this->showAddDomainModal = false;
        $this->tenant->refresh();
        $this->dispatch('domain-added');
    }

    public function confirmRemoveDomain(string $domainId): void
    {
        $this->domainToRemove = $domainId;
        $this->showRemoveDomainModal = true;
    }

    public function removeDomain(): void
    {
        $domain = $this->tenant->domains()->find($this->domainToRemove);

        if ($domain) {
            $domainName = $domain->domain;
            $domain->delete();

            SuperAdminActivityLog::log(
                superAdmin: Auth::guard('superadmin')->user(),
                action: 'tenant_domain_removed',
                description: "Removed domain {$domainName} from tenant",
                tenant: $this->tenant,
                metadata: ['domain' => $domainName],
            );
        }

        $this->domainToRemove = null;
        $this->showRemoveDomainModal = false;
        $this->tenant->refresh();
        $this->dispatch('domain-removed');
    }

    public function impersonate(): void
    {
        $this->validate([
            'impersonationReason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'impersonationReason.required' => 'Please provide a reason for impersonation.',
            'impersonationReason.min' => 'Reason must be at least 10 characters.',
        ]);

        $superAdmin = Auth::guard('superadmin')->user();

        if (! $superAdmin->role->canImpersonateTenants()) {
            $this->addError('impersonationReason', 'You do not have permission to impersonate tenants.');

            return;
        }

        if (! $this->tenant->isActive()) {
            $this->addError('impersonationReason', 'Cannot impersonate inactive or suspended tenants.');

            return;
        }

        if ($this->tenant->domains->isEmpty()) {
            $this->addError('impersonationReason', 'Tenant has no configured domains.');

            return;
        }

        $service = app(TenantImpersonationService::class);
        $log = $service->startImpersonation($superAdmin, $this->tenant, $this->impersonationReason);
        $url = $service->buildImpersonationUrl($this->tenant, $log);

        $this->redirect($url);
    }

    public function openEditModal(): void
    {
        $this->editName = $this->tenant->name ?? '';
        $this->editContactEmail = $this->tenant->contact_email ?? '';
        $this->editContactPhone = $this->tenant->contact_phone ?? '';
        $this->editAddress = $this->tenant->address ?? '';
        $this->showEditModal = true;
    }

    public function resendInvitation(string $userId): void
    {
        $tenant = $this->tenant;

        $tenant->run(function () use ($tenant, $userId): void {
            $user = User::find($userId);

            if (! $user) {
                session()->flash('error', 'User not found.');

                return;
            }

            // Generate new password reset token
            $token = Password::broker('users')->createToken($user);

            // Build reset URL for tenant domain
            $domain = $tenant->domains->first()->domain;
            $scheme = app()->isProduction() ? 'https' : 'http';
            $resetUrl = "{$scheme}://{$domain}/reset-password/{$token}?email=".urlencode($user->email);

            // Send invitation notification
            $user->notify(new TenantAdminInvitationNotification($tenant, $resetUrl));
        });

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'invitation_resent',
            description: "Resent invitation email for tenant: {$this->tenant->name}",
            tenant: $this->tenant,
            metadata: ['user_id' => $userId],
        );

        session()->flash('success', 'Invitation email has been resent.');
    }

    public function updateTenant(): void
    {
        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editContactEmail' => ['nullable', 'email', 'max:255'],
            'editContactPhone' => ['nullable', 'string', 'max:50'],
            'editAddress' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = $this->tenant->only(['name', 'contact_email', 'contact_phone', 'address']);

        $this->tenant->update([
            'name' => $validated['editName'],
            'contact_email' => $validated['editContactEmail'] ?: null,
            'contact_phone' => $validated['editContactPhone'] ?: null,
            'address' => $validated['editAddress'] ?: null,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'tenant_updated',
            description: "Updated tenant: {$this->tenant->name}",
            tenant: $this->tenant,
            metadata: [
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $validated['editName'],
                    'contact_email' => $validated['editContactEmail'],
                    'contact_phone' => $validated['editContactPhone'],
                    'address' => $validated['editAddress'],
                ],
            ],
        );

        $this->showEditModal = false;
        $this->tenant->refresh();
        $this->dispatch('tenant-updated');
    }

    public function render(): View
    {
        return view('livewire.super-admin.tenants.tenant-show', [
            'statuses' => TenantStatus::cases(),
            'subscriptionPlans' => SubscriptionPlan::where('is_active', true)
                ->orderBy('display_order')
                ->get(),
            'recentActivity' => SuperAdminActivityLog::where('tenant_id', $this->tenant->id)
                ->with('superAdmin')
                ->latest('created_at')
                ->take(10)
                ->get(),
        ])->layout('components.layouts.superadmin.app');
    }
}
