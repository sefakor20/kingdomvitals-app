<?php

declare(strict_types=1);

use App\Http\Controllers\ImpersonationController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
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

    // Authenticated routes (require completed onboarding)
    Route::middleware(['auth', 'onboarding.complete'])->group(function (): void {
        // Settings
        Route::redirect('settings', 'settings/profile');
        Route::get('settings/profile', Profile::class)->name('profile.edit');
        Route::get('settings/password', Password::class)->name('user-password.edit');
        Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

        // Branch Management
        Route::get('/branches', \App\Livewire\Branches\BranchIndex::class)
            ->name('branches.index');

        // Branch User Management
        Route::get('/branches/{branch}/users', \App\Livewire\Users\BranchUserIndex::class)
            ->name('branches.users.index');

        // Member Management
        Route::get('/branches/{branch}/members', \App\Livewire\Members\MemberIndex::class)
            ->name('members.index');
        Route::get('/branches/{branch}/members/{member}', \App\Livewire\Members\MemberShow::class)
            ->name('members.show');

        // Cluster Management
        Route::get('/branches/{branch}/clusters', \App\Livewire\Clusters\ClusterIndex::class)
            ->name('clusters.index');
        Route::get('/branches/{branch}/clusters/{cluster}', \App\Livewire\Clusters\ClusterShow::class)
            ->name('clusters.show');

        // Service Management
        Route::get('/branches/{branch}/services', \App\Livewire\Services\ServiceIndex::class)
            ->name('services.index');
        Route::get('/branches/{branch}/services/{service}', \App\Livewire\Services\ServiceShow::class)
            ->name('services.show');

        // Attendance Management
        Route::get('/branches/{branch}/attendance', \App\Livewire\Attendance\AttendanceIndex::class)
            ->name('attendance.index');
        Route::get('/branches/{branch}/services/{service}/check-in', \App\Livewire\Attendance\LiveCheckIn::class)
            ->name('attendance.live-check-in');
        Route::get('/branches/{branch}/services/{service}/dashboard', \App\Livewire\Attendance\AttendanceDashboard::class)
            ->name('attendance.dashboard');
        Route::get('/branches/{branch}/services/{service}/children', \App\Livewire\Attendance\ChildrenCheckIn::class)
            ->name('attendance.children');

        // Household Management
        Route::get('/branches/{branch}/households', \App\Livewire\Households\HouseholdIndex::class)
            ->name('households.index');
        Route::get('/branches/{branch}/households/{household}', \App\Livewire\Households\HouseholdShow::class)
            ->name('households.show');

        // Visitor Management
        Route::get('/branches/{branch}/visitors', \App\Livewire\Visitors\VisitorIndex::class)
            ->name('visitors.index');
        Route::get('/branches/{branch}/visitors/{visitor}', \App\Livewire\Visitors\VisitorShow::class)
            ->name('visitors.show');

        // Financial Management
        Route::get('/branches/{branch}/finance/dashboard', \App\Livewire\Finance\FinanceDashboard::class)
            ->name('finance.dashboard');
        Route::get('/branches/{branch}/finance/donor-engagement', \App\Livewire\Finance\DonorEngagement::class)
            ->name('finance.donor-engagement');
        Route::get('/branches/{branch}/donations', \App\Livewire\Donations\DonationIndex::class)
            ->name('donations.index');
        Route::get('/branches/{branch}/offerings', \App\Livewire\Offerings\OfferingIndex::class)
            ->name('offerings.index');

        // Member Giving History
        Route::get('/branches/{branch}/my-giving', \App\Livewire\Giving\MemberGivingHistory::class)
            ->name('giving.history');
        Route::get('/branches/{branch}/expenses', \App\Livewire\Expenses\ExpenseIndex::class)
            ->name('expenses.index');
        Route::get('/branches/{branch}/expenses/recurring', \App\Livewire\Expenses\RecurringExpenseIndex::class)
            ->name('expenses.recurring');
        Route::get('/branches/{branch}/pledges', \App\Livewire\Pledges\PledgeIndex::class)
            ->name('pledges.index');
        Route::get('/branches/{branch}/campaigns', \App\Livewire\Pledges\CampaignIndex::class)
            ->name('campaigns.index');
        Route::get('/branches/{branch}/budgets', \App\Livewire\Budgets\BudgetIndex::class)
            ->name('budgets.index');
        Route::get('/branches/{branch}/finance/reports', \App\Livewire\Finance\FinanceReports::class)
            ->name('finance.reports');

        // SMS Management
        Route::get('/branches/{branch}/sms', \App\Livewire\Sms\SmsIndex::class)
            ->name('sms.index');
        Route::get('/branches/{branch}/sms/compose', \App\Livewire\Sms\SmsCompose::class)
            ->name('sms.compose');
        Route::get('/branches/{branch}/sms/templates', \App\Livewire\Sms\SmsTemplateIndex::class)
            ->name('sms.templates');
        Route::get('/branches/{branch}/sms/analytics', \App\Livewire\Sms\SmsAnalytics::class)
            ->name('sms.analytics');

        // Equipment Management
        Route::get('/branches/{branch}/equipment', \App\Livewire\Equipment\EquipmentIndex::class)
            ->name('equipment.index');
        Route::get('/branches/{branch}/equipment/{equipment}', \App\Livewire\Equipment\EquipmentShow::class)
            ->name('equipment.show');

        // Prayer Request Management
        Route::get('/branches/{branch}/prayer-requests', \App\Livewire\PrayerRequests\PrayerRequestIndex::class)
            ->name('prayer-requests.index');
        Route::get('/branches/{branch}/prayer-requests/{prayerRequest}', \App\Livewire\PrayerRequests\PrayerRequestShow::class)
            ->name('prayer-requests.show');

        // Children's Ministry Management
        Route::get('/branches/{branch}/children', \App\Livewire\Children\ChildrenDirectory::class)
            ->name('children.index');
        Route::get('/branches/{branch}/children/dashboard', \App\Livewire\Children\ChildrenDashboard::class)
            ->name('children.dashboard');
        Route::get('/branches/{branch}/children/age-groups', \App\Livewire\Children\AgeGroupIndex::class)
            ->name('children.age-groups');

        // Report Center
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

        // Branch Settings
        Route::get('/branches/{branch}/settings', \App\Livewire\Branches\BranchSettings::class)
            ->name('branches.settings');
    });
});
