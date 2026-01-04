<?php

namespace App\Providers;

use App\Models\Tenant\Budget;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgeCampaign;
use App\Models\Tenant\RecurringExpense;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\SmsTemplate;
use App\Models\Tenant\UserBranchAccess;
use App\Models\Tenant\VisitorFollowUp;
use App\Policies\BudgetPolicy;
use App\Policies\DonationPolicy;
use App\Policies\EquipmentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\MemberPolicy;
use App\Policies\PledgeCampaignPolicy;
use App\Policies\PledgePolicy;
use App\Policies\RecurringExpensePolicy;
use App\Policies\ReportPolicy;
use App\Policies\SmsLogPolicy;
use App\Policies\SmsTemplatePolicy;
use App\Policies\UserBranchAccessPolicy;
use App\Policies\VisitorFollowUpPolicy;
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
        //
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

        // Register Report gates (not model-based)
        Gate::define('viewReports', [ReportPolicy::class, 'viewReports']);
        Gate::define('exportReports', [ReportPolicy::class, 'exportReports']);

        // Load central/landlord migrations
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/landlord'),
        ]);

        // Configure Livewire to use tenant middleware for AJAX updates
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
                ]);
        });
    }
}
