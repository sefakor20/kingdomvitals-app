<?php

declare(strict_types=1);

use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Livewire\Auth\AcceptBranchInvitation;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Organization;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\PaymentHistory;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Subscription;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware(['web'])->group(function (): void {
    // Impersonation routes (before auth middleware)
    Route::get('/impersonate/enter', [ImpersonationController::class, 'enter'])
        ->name('impersonate.enter');
    Route::post('/impersonate/exit', [ImpersonationController::class, 'exit'])
        ->name('impersonate.exit');

    // Include all Fortify authentication routes (login, register, password reset, 2FA, etc.)
    require base_path('vendor/laravel/fortify/routes/routes.php');

    // Onboarding routes (auth but no onboarding.complete middleware)
    Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
        Route::get('/', \App\Livewire\Onboarding\OnboardingWizard::class)->name('index');
    });

    Route::get('/', function () {
        return redirect()->route('dashboard');
    })->name('home');

    // Dashboard
    Route::get('/dashboard', \App\Livewire\Dashboard::class)
        ->middleware(['auth', 'verified', 'onboarding.complete'])
        ->name('dashboard');

    // Mobile Self Check-in (public access via token)
    Route::get('/checkin/{token}', \App\Livewire\Attendance\MobileSelfCheckIn::class)
        ->name('checkin.qr');

    // Public giving page (no auth required)
    Route::get('/branches/{branch}/give', \App\Livewire\Giving\PublicGivingForm::class)
        ->name('giving.form');

    // Branch user invitation acceptance (guest route)
    Route::get('/invitations/{token}/accept', AcceptBranchInvitation::class)
        ->name('invitations.accept')
        ->middleware('guest');

    // Also accessible at /give for convenience
    Route::get('/give', function () {
        // Redirect to the main branch giving page
        $mainBranch = \App\Models\Tenant\Branch::where('is_main', true)->first();
        if ($mainBranch) {
            return redirect()->route('giving.form', $mainBranch);
        }

        return redirect()->route('dashboard');
    })->name('giving.public');

    // Paystack webhook (no auth, no CSRF)
    Route::post('/webhooks/paystack', [\App\Http\Controllers\Webhooks\PaystackWebhookController::class, 'handle'])
        ->name('webhooks.paystack')
        ->withoutMiddleware(['web']);

    // Upgrade required page (auth required but no onboarding check)
    Route::middleware(['auth'])->group(function (): void {
        Route::get('/upgrade', function () {
            $module = request('module');
            $moduleName = $module
                ? \App\Enums\PlanModule::tryFrom($module)?->label() ?? __('This feature')
                : __('This feature');

            return view('upgrade-required', compact('moduleName'));
        })->name('upgrade.required');

        // Plan upgrade routes
        Route::get('/plans', \App\Livewire\Upgrade\PlansIndex::class)
            ->name('plans.index');
        Route::get('/plans/{plan}/checkout', \App\Livewire\Upgrade\PlanCheckout::class)
            ->name('plans.checkout');
    });

    // Authenticated routes (require completed onboarding)
    Route::middleware(['auth', 'onboarding.complete'])->group(function (): void {
        // Settings (no module restriction)
        Route::redirect('settings', 'settings/profile');
        Route::get('settings/profile', Profile::class)->name('profile.edit');
        Route::get('settings/password', Password::class)->name('user-password.edit');
        Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
        Route::get('settings/organization', Organization::class)->name('organization.edit');
        Route::get('settings/subscription', Subscription::class)->name('subscription.show');
        Route::get('settings/payments', PaymentHistory::class)->name('payments.history');

        // Invoice PDF Download
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])
            ->name('invoices.download');

        // Branch Management (no module restriction - core feature)
        Route::get('/branches', \App\Livewire\Branches\BranchIndex::class)
            ->name('branches.index');
        Route::get('/branches/{branch}/settings', \App\Livewire\Branches\BranchSettings::class)
            ->name('branches.settings');

        // Branch User Management (no module restriction - core feature)
        Route::get('/branches/{branch}/users', \App\Livewire\Users\BranchUserIndex::class)
            ->name('branches.users.index');

        // Member Management (requires members module)
        Route::middleware(['module:members'])->group(function (): void {
            Route::get('/branches/{branch}/members', \App\Livewire\Members\MemberIndex::class)
                ->name('members.index');
            Route::get('/branches/{branch}/members/{member}/qr-print', \App\Livewire\Members\MemberQrPrint::class)
                ->name('members.qr-print');
            Route::get('/branches/{branch}/members/{member}', \App\Livewire\Members\MemberShow::class)
                ->name('members.show');
        });

        // Cluster Management (requires clusters module)
        Route::middleware(['module:clusters'])->group(function (): void {
            Route::get('/branches/{branch}/clusters', \App\Livewire\Clusters\ClusterIndex::class)
                ->name('clusters.index');
            Route::get('/branches/{branch}/clusters/{cluster}', \App\Livewire\Clusters\ClusterShow::class)
                ->name('clusters.show');
        });

        // Service Management (requires services module)
        Route::middleware(['module:services'])->group(function (): void {
            Route::get('/branches/{branch}/services', \App\Livewire\Services\ServiceIndex::class)
                ->name('services.index');
            Route::get('/branches/{branch}/services/{service}', \App\Livewire\Services\ServiceShow::class)
                ->name('services.show');
        });

        // Attendance Management (requires attendance module)
        Route::middleware(['module:attendance'])->group(function (): void {
            Route::get('/branches/{branch}/attendance', \App\Livewire\Attendance\AttendanceIndex::class)
                ->name('attendance.index');
            Route::get('/branches/{branch}/attendance/analytics', \App\Livewire\Attendance\AttendanceAnalytics::class)
                ->name('attendance.analytics');
            Route::get('/branches/{branch}/services/{service}/check-in', \App\Livewire\Attendance\LiveCheckIn::class)
                ->name('attendance.live-check-in');
            Route::get('/branches/{branch}/services/{service}/dashboard', \App\Livewire\Attendance\AttendanceDashboard::class)
                ->name('attendance.dashboard');
            Route::get('/branches/{branch}/services/{service}/children', \App\Livewire\Attendance\ChildrenCheckIn::class)
                ->name('attendance.children');
        });

        // Household Management (requires households module)
        Route::middleware(['module:households'])->group(function (): void {
            Route::get('/branches/{branch}/households', \App\Livewire\Households\HouseholdIndex::class)
                ->name('households.index');
            Route::get('/branches/{branch}/households/{household}', \App\Livewire\Households\HouseholdShow::class)
                ->name('households.show');
        });

        // Visitor Management (requires visitors module)
        Route::middleware(['module:visitors'])->group(function (): void {
            Route::get('/branches/{branch}/visitors', \App\Livewire\Visitors\VisitorIndex::class)
                ->name('visitors.index');
            Route::get('/branches/{branch}/visitors/analytics', \App\Livewire\Visitors\VisitorAnalytics::class)
                ->name('visitors.analytics');
            Route::get('/branches/{branch}/visitors/follow-ups', \App\Livewire\Visitors\FollowUpQueue::class)
                ->name('visitors.follow-ups');
            Route::get('/branches/{branch}/visitors/follow-up-templates', \App\Livewire\Visitors\FollowUpTemplateIndex::class)
                ->name('visitors.follow-up-templates');
            Route::get('/branches/{branch}/visitors/{visitor}', \App\Livewire\Visitors\VisitorShow::class)
                ->name('visitors.show');
        });

        // Donations Management (requires donations module)
        Route::middleware(['module:donations'])->group(function (): void {
            Route::get('/branches/{branch}/finance/dashboard', \App\Livewire\Finance\FinanceDashboard::class)
                ->name('finance.dashboard');
            Route::get('/branches/{branch}/finance/donor-engagement', \App\Livewire\Finance\DonorEngagement::class)
                ->name('finance.donor-engagement');
            Route::get('/branches/{branch}/donations', \App\Livewire\Donations\DonationIndex::class)
                ->name('donations.index');
            Route::get('/branches/{branch}/offerings', \App\Livewire\Offerings\OfferingIndex::class)
                ->name('offerings.index');
            Route::get('/branches/{branch}/my-giving', \App\Livewire\Giving\MemberGivingHistory::class)
                ->name('giving.history');
        });

        // Expenses Management (requires expenses module)
        Route::middleware(['module:expenses'])->group(function (): void {
            Route::get('/branches/{branch}/expenses', \App\Livewire\Expenses\ExpenseIndex::class)
                ->name('expenses.index');
            Route::get('/branches/{branch}/expenses/recurring', \App\Livewire\Expenses\RecurringExpenseIndex::class)
                ->name('expenses.recurring');
        });

        // Pledges Management (requires pledges module)
        Route::middleware(['module:pledges'])->group(function (): void {
            Route::get('/branches/{branch}/pledges', \App\Livewire\Pledges\PledgeIndex::class)
                ->name('pledges.index');
            Route::get('/branches/{branch}/campaigns', \App\Livewire\Pledges\CampaignIndex::class)
                ->name('campaigns.index');
        });

        // Budgets Management (requires budgets module)
        Route::middleware(['module:budgets'])->group(function (): void {
            Route::get('/branches/{branch}/budgets', \App\Livewire\Budgets\BudgetIndex::class)
                ->name('budgets.index');
            Route::get('/branches/{branch}/finance/reports', \App\Livewire\Finance\FinanceReports::class)
                ->name('finance.reports');
        });

        // SMS Management (requires sms module)
        Route::middleware(['module:sms'])->group(function (): void {
            Route::get('/branches/{branch}/sms', \App\Livewire\Sms\SmsIndex::class)
                ->name('sms.index');
            Route::get('/branches/{branch}/sms/compose', \App\Livewire\Sms\SmsCompose::class)
                ->name('sms.compose');
            Route::get('/branches/{branch}/sms/templates', \App\Livewire\Sms\SmsTemplateIndex::class)
                ->name('sms.templates');
            Route::get('/branches/{branch}/sms/analytics', \App\Livewire\Sms\SmsAnalytics::class)
                ->name('sms.analytics');
        });

        // Equipment Management (requires equipment module)
        Route::middleware(['module:equipment'])->group(function (): void {
            Route::get('/branches/{branch}/equipment', \App\Livewire\Equipment\EquipmentIndex::class)
                ->name('equipment.index');
            Route::get('/branches/{branch}/equipment/{equipment}', \App\Livewire\Equipment\EquipmentShow::class)
                ->name('equipment.show');
        });

        // Prayer Request Management (requires prayer_requests module)
        Route::middleware(['module:prayer_requests'])->group(function (): void {
            Route::get('/branches/{branch}/prayer-requests', \App\Livewire\PrayerRequests\PrayerRequestIndex::class)
                ->name('prayer-requests.index');
            Route::get('/branches/{branch}/prayer-requests/{prayerRequest}', \App\Livewire\PrayerRequests\PrayerRequestShow::class)
                ->name('prayer-requests.show');
        });

        // Duty Roster Management (requires duty_roster module)
        Route::middleware(['module:duty_roster'])->group(function (): void {
            Route::get('/branches/{branch}/duty-rosters', \App\Livewire\DutyRosters\DutyRosterIndex::class)
                ->name('duty-rosters.index');
            Route::get('/branches/{branch}/duty-rosters/pools', \App\Livewire\DutyRosters\DutyRosterPoolIndex::class)
                ->name('duty-rosters.pools.index');
            Route::get('/branches/{branch}/duty-rosters/availability', \App\Livewire\DutyRosters\MemberAvailabilityIndex::class)
                ->name('duty-rosters.availability.index');
            Route::get('/branches/{branch}/duty-rosters/generate', \App\Livewire\DutyRosters\DutyRosterGenerationWizard::class)
                ->name('duty-rosters.generate');
            Route::get('/branches/{branch}/duty-rosters/print', \App\Livewire\DutyRosters\DutyRosterPrint::class)
                ->name('duty-rosters.print');
            Route::get('/branches/{branch}/duty-rosters/{dutyRoster}', \App\Livewire\DutyRosters\DutyRosterShow::class)
                ->name('duty-rosters.show');
        });

        // Children's Ministry Management (requires children module)
        Route::middleware(['module:children'])->group(function (): void {
            Route::get('/branches/{branch}/children', \App\Livewire\Children\ChildrenDirectory::class)
                ->name('children.index');
            Route::get('/branches/{branch}/children/dashboard', \App\Livewire\Children\ChildrenDashboard::class)
                ->name('children.dashboard');
            Route::get('/branches/{branch}/children/age-groups', \App\Livewire\Children\AgeGroupIndex::class)
                ->name('children.age-groups');
        });

        // Report Center (requires reports module)
        Route::middleware(['module:reports'])->group(function (): void {
            Route::get('/branches/{branch}/reports', \App\Livewire\Reports\ReportCenter::class)
                ->name('reports.index');

            // Membership Reports
            Route::get('/branches/{branch}/reports/membership/directory', \App\Livewire\Reports\Membership\MemberDirectory::class)
                ->name('reports.membership.directory');
            Route::get('/branches/{branch}/reports/membership/new-members', \App\Livewire\Reports\Membership\NewMembersReport::class)
                ->name('reports.membership.new-members');
            Route::get('/branches/{branch}/reports/membership/inactive', \App\Livewire\Reports\Membership\InactiveMembersReport::class)
                ->name('reports.membership.inactive');
            Route::get('/branches/{branch}/reports/membership/demographics', \App\Livewire\Reports\Membership\MemberDemographics::class)
                ->name('reports.membership.demographics');
            Route::get('/branches/{branch}/reports/membership/growth', \App\Livewire\Reports\Membership\MemberGrowthTrends::class)
                ->name('reports.membership.growth');

            // Attendance Reports
            Route::get('/branches/{branch}/reports/attendance/weekly', \App\Livewire\Reports\Attendance\WeeklyAttendanceSummary::class)
                ->name('reports.attendance.weekly');
            Route::get('/branches/{branch}/reports/attendance/monthly', \App\Livewire\Reports\Attendance\MonthlyAttendanceComparison::class)
                ->name('reports.attendance.monthly');
            Route::get('/branches/{branch}/reports/attendance/by-service', \App\Livewire\Reports\Attendance\ServiceWiseAttendance::class)
                ->name('reports.attendance.by-service');
            Route::get('/branches/{branch}/reports/attendance/absent-members', \App\Livewire\Reports\Attendance\AbsentMembersReport::class)
                ->name('reports.attendance.absent-members');
            Route::get('/branches/{branch}/reports/attendance/visitors', \App\Livewire\Reports\Attendance\FirstTimeVisitorsReport::class)
                ->name('reports.attendance.visitors');
        });
    });
});
