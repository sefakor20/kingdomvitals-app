<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Plans;

use App\Enums\SupportLevel;
use App\Livewire\Concerns\HasReportExport;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminActivityLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanIndex extends Component
{
    use HasReportExport;

    // Modal states
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?string $editPlanId = null;

    public ?string $deletePlanId = null;

    public int $deleteSubscriberCount = 0;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $priceMonthly = '0';

    public string $priceAnnual = '0';

    public ?int $maxMembers = null;

    public ?int $maxBranches = null;

    public int $storageQuotaGb = 5;

    public ?int $smsCreditsMonthly = null;

    public string $featuresInput = '';

    public string $supportLevel = 'community';

    public bool $isActive = true;

    public bool $isDefault = false;

    public int $displayOrder = 0;

    public function updatedName(): void
    {
        if (! $this->editPlanId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->priceMonthly = '0';
        $this->priceAnnual = '0';
        $this->maxMembers = null;
        $this->maxBranches = null;
        $this->storageQuotaGb = 5;
        $this->smsCreditsMonthly = null;
        $this->featuresInput = '';
        $this->supportLevel = 'community';
        $this->isActive = true;
        $this->isDefault = false;
        $this->displayOrder = 0;
        $this->resetValidation();
    }

    public function createPlan(): void
    {
        $this->ensureCanManagePlans();

        $this->validate($this->validationRules());

        // Parse features from comma-separated input
        $features = $this->parseFeatures($this->featuresInput);

        // If setting as default, remove default from other plans
        if ($this->isDefault) {
            SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan = SubscriptionPlan::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'price_monthly' => $this->priceMonthly,
            'price_annual' => $this->priceAnnual,
            'max_members' => $this->maxMembers,
            'max_branches' => $this->maxBranches,
            'storage_quota_gb' => $this->storageQuotaGb,
            'sms_credits_monthly' => $this->smsCreditsMonthly,
            'features' => $features,
            'support_level' => SupportLevel::from($this->supportLevel),
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
            'display_order' => $this->displayOrder,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'plan_created',
            description: "Created subscription plan: {$plan->name}",
            metadata: [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'price_monthly' => $plan->price_monthly,
            ],
        );

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('plan-created');
    }

    public function openEditModal(string $planId): void
    {
        $this->ensureCanManagePlans();

        $plan = SubscriptionPlan::findOrFail($planId);

        $this->editPlanId = $plan->id;
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->description = $plan->description ?? '';
        $this->priceMonthly = (string) $plan->price_monthly;
        $this->priceAnnual = (string) $plan->price_annual;
        $this->maxMembers = $plan->max_members;
        $this->maxBranches = $plan->max_branches;
        $this->storageQuotaGb = $plan->storage_quota_gb;
        $this->smsCreditsMonthly = $plan->sms_credits_monthly;
        $this->featuresInput = $plan->features ? implode(', ', $plan->features) : '';
        $this->supportLevel = $plan->support_level->value;
        $this->isActive = $plan->is_active;
        $this->isDefault = $plan->is_default;
        $this->displayOrder = $plan->display_order;

        $this->showEditModal = true;
    }

    public function updatePlan(): void
    {
        $this->ensureCanManagePlans();

        $plan = SubscriptionPlan::findOrFail($this->editPlanId);

        $rules = $this->validationRules();
        $rules['slug'] = ['required', 'string', 'max:100', 'unique:subscription_plans,slug,'.$plan->id];

        $this->validate($rules);

        $features = $this->parseFeatures($this->featuresInput);

        // If setting as default, remove default from other plans
        if ($this->isDefault && ! $plan->is_default) {
            SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
        }

        $oldValues = $plan->only(['name', 'slug', 'price_monthly', 'price_annual', 'is_active', 'is_default']);

        $plan->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'price_monthly' => $this->priceMonthly,
            'price_annual' => $this->priceAnnual,
            'max_members' => $this->maxMembers,
            'max_branches' => $this->maxBranches,
            'storage_quota_gb' => $this->storageQuotaGb,
            'sms_credits_monthly' => $this->smsCreditsMonthly,
            'features' => $features,
            'support_level' => SupportLevel::from($this->supportLevel),
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
            'display_order' => $this->displayOrder,
        ]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'plan_updated',
            description: "Updated subscription plan: {$plan->name}",
            metadata: [
                'plan_id' => $plan->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'price_monthly' => $this->priceMonthly,
                    'price_annual' => $this->priceAnnual,
                    'is_active' => $this->isActive,
                    'is_default' => $this->isDefault,
                ],
            ],
        );

        $this->showEditModal = false;
        $this->editPlanId = null;
        $this->resetForm();
        $this->dispatch('plan-updated');
    }

    public function confirmDelete(string $planId): void
    {
        $this->ensureCanManagePlans();

        $this->deletePlanId = $planId;
        $this->deleteSubscriberCount = Tenant::where('subscription_id', $planId)->count();
        $this->showDeleteModal = true;
    }

    public function deletePlan(): void
    {
        $this->ensureCanManagePlans();

        $plan = SubscriptionPlan::findOrFail($this->deletePlanId);

        // Check for subscribers
        $subscriberCount = Tenant::where('subscription_id', $plan->id)->count();
        if ($subscriberCount > 0) {
            $this->addError('delete', "Cannot delete plan with {$subscriberCount} active subscriber(s). Reassign tenants first.");
            $this->showDeleteModal = false;

            return;
        }

        $planName = $plan->name;
        $planId = $plan->id;

        $plan->delete();

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'plan_deleted',
            description: "Deleted subscription plan: {$planName}",
            metadata: ['plan_id' => $planId, 'plan_name' => $planName],
        );

        $this->showDeleteModal = false;
        $this->deletePlanId = null;
        $this->deleteSubscriberCount = 0;
        $this->dispatch('plan-deleted');
    }

    public function toggleActive(string $planId): void
    {
        $this->ensureCanManagePlans();

        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);

        $action = $plan->is_active ? 'plan_activated' : 'plan_deactivated';
        $description = $plan->is_active ? 'Activated' : 'Deactivated';

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: $action,
            description: "{$description} subscription plan: {$plan->name}",
            metadata: ['plan_id' => $plan->id, 'is_active' => $plan->is_active],
        );

        $this->dispatch('plan-status-changed');
    }

    public function setAsDefault(string $planId): void
    {
        $this->ensureCanManagePlans();

        // Remove default from all plans
        SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_default' => true]);

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'plan_set_default',
            description: "Set default subscription plan: {$plan->name}",
            metadata: ['plan_id' => $plan->id],
        );

        $this->dispatch('plan-default-changed');
    }

    public function exportCsv(): StreamedResponse
    {
        $plans = SubscriptionPlan::orderBy('display_order')->orderBy('price_monthly')->get();

        $data = $plans->map(fn (SubscriptionPlan $plan): array => [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price_monthly' => Number::currency((float) $plan->price_monthly, in: 'GHS'),
            'price_annual' => Number::currency((float) $plan->price_annual, in: 'GHS'),
            'max_members' => $plan->max_members ?? 'Unlimited',
            'max_branches' => $plan->max_branches ?? 'Unlimited',
            'storage_quota_gb' => $plan->storage_quota_gb,
            'sms_credits_monthly' => $plan->sms_credits_monthly ?? 'N/A',
            'support_level' => $plan->support_level->label(),
            'is_active' => $plan->is_active ? 'Yes' : 'No',
            'is_default' => $plan->is_default ? 'Yes' : 'No',
        ]);

        $headers = [
            'Name',
            'Slug',
            'Monthly Price (GHS)',
            'Annual Price (GHS)',
            'Max Members',
            'Max Branches',
            'Storage (GB)',
            'SMS Credits/Month',
            'Support Level',
            'Active',
            'Default',
        ];

        SuperAdminActivityLog::log(
            superAdmin: Auth::guard('superadmin')->user(),
            action: 'export_plans',
            description: 'Exported subscription plans to CSV',
            metadata: ['record_count' => $plans->count()],
        );

        $filename = 'subscription-plans-'.now()->format('Y-m-d').'.csv';

        return $this->exportToCsv($data, $headers, $filename);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string'],
            'priceMonthly' => ['required', 'numeric', 'min:0'],
            'priceAnnual' => ['required', 'numeric', 'min:0'],
            'maxMembers' => ['nullable', 'integer', 'min:1'],
            'maxBranches' => ['nullable', 'integer', 'min:1'],
            'storageQuotaGb' => ['required', 'integer', 'min:1'],
            'smsCreditsMonthly' => ['nullable', 'integer', 'min:0'],
            'supportLevel' => ['required', 'in:community,email,priority'],
            'displayOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function parseFeatures(string $input): ?array
    {
        if (in_array(trim($input), ['', '0'], true)) {
            return null;
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $input)),
            fn ($feature): bool => ! empty($feature)
        ));
    }

    private function ensureCanManagePlans(): void
    {
        $currentUser = Auth::guard('superadmin')->user();

        if (! $currentUser->role->canManageSuperAdmins()) {
            abort(403, 'You do not have permission to manage subscription plans.');
        }
    }

    public function render(): View
    {
        $currentUser = Auth::guard('superadmin')->user();
        $canManage = $currentUser->role->canManageSuperAdmins();

        return view('livewire.super-admin.plans.plan-index', [
            'plans' => SubscriptionPlan::orderBy('display_order')->orderBy('price_monthly')->get(),
            'supportLevels' => SupportLevel::cases(),
            'canManage' => $canManage,
        ])->layout('components.layouts.superadmin.app');
    }
}
