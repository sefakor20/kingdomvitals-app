<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\Tenant\Budget;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgeCampaign;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\RecurringExpense;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\VisitorFollowUp;
use App\Observers\SmsLogObserver;
use App\Observers\TenantObserver;
use App\Policies\BudgetPolicy;
use App\Policies\ChildrenCheckinSecurityPolicy;
use App\Policies\DonationPolicy;
use App\Policies\EquipmentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\HouseholdPolicy;
use App\Policies\MemberPolicy;
use App\Policies\PledgeCampaignPolicy;
use App\Policies\PledgePolicy;
use App\Policies\PrayerRequestPolicy;
use App\Policies\RecurringExpensePolicy;
use App\Policies\ReportPolicy;
use App\Policies\SmsLogPolicy;
use App\Policies\SmsTemplatePolicy;
use App\Policies\UserBranchAccessPolicy;
use App\Policies\VisitorFollowUpPolicy;
use App\Services\PlanAccessService;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SystemSettingService::class);
        $this->app->scoped(PlanAccessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(UserBranchAccess::class, UserBranchAccessPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(VisitorFollowUp::class, VisitorFollowUpPolicy::class);
        Gate::policy(Donation::class, DonationPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(Pledge::class, PledgePolicy::class);
        Gate::policy(PledgeCampaign::class, PledgeCampaignPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(RecurringExpense::class, RecurringExpensePolicy::class);
        Gate::policy(SmsLog::class, SmsLogPolicy::class);
        Gate::policy(SmsTemplate::class, SmsTemplatePolicy::class);
        Gate::policy(Equipment::class, EquipmentPolicy::class);
        Gate::policy(Household::class, HouseholdPolicy::class);
        Gate::policy(ChildrenCheckinSecurity::class, ChildrenCheckinSecurityPolicy::class);
        Gate::policy(PrayerRequest::class, PrayerRequestPolicy::class);

        // Register Report gates (not model-based)
        Gate::define('viewReports', [ReportPolicy::class, 'viewReports']);
        Gate::define('exportReports', [ReportPolicy::class, 'exportReports']);

        // Load central/landlord migrations
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/landlord'),
        ]);

        // Configure Livewire update route for multi-tenancy
        Livewire::setUpdateRoute(function ($handle) {
            $centralDomains = config('tenancy.central_domains', []);
            $currentDomain = request()->getHost();

            // For central domains (super admin, etc.), use only web middleware
            if (in_array($currentDomain, $centralDomains)) {
                return Route::post('/livewire/update', $handle)
                    ->middleware(['web']);
            }

            // For tenant domains, include tenancy middleware
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                ]);
        });

        $this->registerPlanAccessDirectives();

        // Register observers
        Tenant::observe(TenantObserver::class);
        SmsLog::observe(SmsLogObserver::class);
    }

    /**
     * Register Blade directives for plan-based access control.
     */
    private function registerPlanAccessDirectives(): void
    {
        // @module('members') ... @endmodule
        Blade::if('module', function (string $module) {
            return app(PlanAccessService::class)->hasModule($module);
        });

        // @feature('advanced_reports') ... @endfeature
        Blade::if('feature', function (string $feature) {
            return app(PlanAccessService::class)->hasFeature($feature);
        });

        // @canCreateMember ... @endcanCreateMember
        Blade::if('canCreateMember', function () {
            return app(PlanAccessService::class)->canCreateMember();
        });

        // @canCreateBranch ... @endcanCreateBranch
        Blade::if('canCreateBranch', function () {
            return app(PlanAccessService::class)->canCreateBranch();
        });

        // @canSendSms ... @endcanSendSms
        Blade::if('canSendSms', function (int $count = 1) {
            return app(PlanAccessService::class)->canSendSms($count);
        });

        // @quotaWarning('members') ... @endquotaWarning
        Blade::if('quotaWarning', function (string $quotaType, int $threshold = 80) {
            return app(PlanAccessService::class)->isQuotaWarning($quotaType, $threshold);
        });

        // @canUploadFile($sizeBytes) ... @endcanUploadFile
        Blade::if('canUploadFile', function (int $sizeBytes = 0) {
            return app(PlanAccessService::class)->canUploadFile($sizeBytes);
        });
    }
}
