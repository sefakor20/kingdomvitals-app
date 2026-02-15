<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\Tenant\Budget;
use App\Models\Tenant\ChildrenCheckinSecurity;
use App\Models\Tenant\Donation;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Expense;
use App\Models\Tenant\FollowUpTemplate;
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
use App\Policies\DutyRosterPolicy;
use App\Policies\EquipmentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FollowUpTemplatePolicy;
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
use App\Services\CurrencyFormatter;
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
        $this->app->singleton(CurrencyFormatter::class);
        $this->app->scoped(PlanAccessService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePreventAccessFromCentralDomains();

        // Register policies
        Gate::policy(UserBranchAccess::class, UserBranchAccessPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(VisitorFollowUp::class, VisitorFollowUpPolicy::class);
        Gate::policy(FollowUpTemplate::class, FollowUpTemplatePolicy::class);
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
        Gate::policy(DutyRoster::class, DutyRosterPolicy::class);

        // Register Report gates (not model-based)
        Gate::define('viewReports', [ReportPolicy::class, 'viewReports']);
        Gate::define('exportReports', [ReportPolicy::class, 'exportReports']);

        // Load central/landlord migrations
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/landlord'),
        ]);

        // Configure Livewire update route for multi-tenancy
        // For central domains (admin panel), skip tenancy initialization and continue.
        // For tenant domains, initialize tenancy normally.
        $this->configureInitializeTenancyByDomainOnFail();

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                ]);
        });

        $this->registerPlanAccessDirectives();
        $this->registerCurrencyDirectives();

        // Register observers
        Tenant::observe(TenantObserver::class);
        SmsLog::observe(SmsLogObserver::class);
    }

    /**
     * Configure PreventAccessFromCentralDomains middleware to redirect admin users.
     *
     * When someone accesses tenant routes (like /login) from an admin domain,
     * redirect them to the superadmin login instead of showing 404.
     */
    private function configurePreventAccessFromCentralDomains(): void
    {
        \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::$abortRequest = function ($request, $next) {
            $currentHost = $request->getHost();
            $superadminDomain = config('app.superadmin_domain', 'admin.localhost');

            // If on admin domain, redirect to superadmin login
            if ($currentHost === $superadminDomain || str_starts_with($currentHost, 'admin.')) {
                return redirect()->route('superadmin.login');
            }

            // For other central domains (localhost, etc.), show 404
            abort(404);
        };
    }

    /**
     * Configure InitializeTenancyByDomain middleware to allow central domains.
     *
     * When tenant identification fails, check if the request is from a central domain.
     * If so, allow the request to continue without tenancy (for admin panel, landing page, etc.).
     * If not a central domain, throw the original exception.
     */
    private function configureInitializeTenancyByDomainOnFail(): void
    {
        \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
            $centralDomains = config('tenancy.central_domains', []);

            // If this is a central domain, continue without tenancy
            if (in_array($request->getHost(), $centralDomains)) {
                return $next($request);
            }

            // For non-central domains, throw the exception
            throw $exception;
        };
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

    /**
     * Register Blade directives for currency formatting.
     */
    private function registerCurrencyDirectives(): void
    {
        // @money($amount, $currency) → ₵100.00 or $100.00
        Blade::directive('money', function (string $expression): string {
            return "<?php echo app(\App\Services\CurrencyFormatter::class)->format({$expression}); ?>";
        });

        // @moneyCode($amount, $currency) → GHS 100.00 or USD 100.00
        Blade::directive('moneyCode', function (string $expression): string {
            return "<?php echo app(\App\Services\CurrencyFormatter::class)->formatWithCode({$expression}); ?>";
        });

        // @currencySymbol($currency) → ₵ or $
        Blade::directive('currencySymbol', function (string $expression): string {
            return "<?php echo app(\App\Services\CurrencyFormatter::class)->symbol({$expression}); ?>";
        });
    }
}
