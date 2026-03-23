<?php

declare(strict_types=1);

use App\Enums\PlanModule;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\EventTicketController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Livewire\ActivityLogs\ActivityLogIndex;
use App\Livewire\AI\InsightsDashboard;
use App\Livewire\Attendance\AttendanceAnalytics;
use App\Livewire\Attendance\AttendanceDashboard;
use App\Livewire\Attendance\AttendanceIndex;
use App\Livewire\Attendance\ChildrenCheckIn;
use App\Livewire\Attendance\LiveCheckIn;
use App\Livewire\Attendance\MobileSelfCheckIn;
use App\Livewire\Auth\AcceptBranchInvitation;
use App\Livewire\Auth\AcceptMemberInvitation;
use App\Livewire\Branches\AlertSettings;
use App\Livewire\Branches\BranchIndex;
use App\Livewire\Branches\BranchSettings;
use App\Livewire\Budgets\BudgetIndex;
use App\Livewire\Chatbot\ConversationMonitor;
use App\Livewire\Children\AgeGroupIndex;
use App\Livewire\Children\ChildrenDashboard;
use App\Livewire\Children\ChildrenDirectory;
use App\Livewire\Clusters\ClusterIndex;
use App\Livewire\Clusters\ClusterShow;
use App\Livewire\Dashboard;
use App\Livewire\Donations\DonationIndex;
use App\Livewire\DutyRosters\DutyRosterGenerationWizard;
use App\Livewire\DutyRosters\DutyRosterIndex;
use App\Livewire\DutyRosters\DutyRosterPoolIndex;
use App\Livewire\DutyRosters\DutyRosterPrint;
use App\Livewire\DutyRosters\DutyRosterShow;
use App\Livewire\DutyRosters\MemberAvailabilityIndex;
use App\Livewire\Email\EmailAnalytics;
use App\Livewire\Email\EmailCompose;
use App\Livewire\Email\EmailIndex;
use App\Livewire\Email\EmailTemplateIndex;
use App\Livewire\Equipment\EquipmentIndex;
use App\Livewire\Equipment\EquipmentShow;
use App\Livewire\Events\EventAttendancePredictions;
use App\Livewire\Events\EventCheckIn;
use App\Livewire\Events\EventIndex;
use App\Livewire\Events\EventShow;
use App\Livewire\Events\Public\PublicEventDetails;
use App\Livewire\Events\Public\PublicEventRegistration;
use App\Livewire\Expenses\ExpenseIndex;
use App\Livewire\Expenses\RecurringExpenseIndex;
use App\Livewire\Finance\DonorEngagement;
use App\Livewire\Finance\FinanceDashboard;
use App\Livewire\Finance\FinanceReports;
use App\Livewire\Giving\GivingIntelligenceDashboard;
use App\Livewire\Giving\MemberGivingHistory;
use App\Livewire\Giving\PublicGivingForm;
use App\Livewire\Households\HouseholdIndex;
use App\Livewire\Households\HouseholdShow;
use App\Livewire\Member\MemberAppearance;
use App\Livewire\Member\MemberAttendance;
use App\Livewire\Member\MemberContactInfo;
use App\Livewire\Member\MemberDashboard;
use App\Livewire\Member\MemberEvents;
use App\Livewire\Member\MemberGiving;
use App\Livewire\Member\MemberHousehold;
use App\Livewire\Member\MemberPassword;
use App\Livewire\Member\MemberPledges;
use App\Livewire\Member\MemberProfile;
use App\Livewire\Member\MemberTwoFactor;
use App\Livewire\Members\MemberCardsBulkPrint;
use App\Livewire\Members\MemberIndex;
use App\Livewire\Members\MemberQrPrint;
use App\Livewire\Members\MemberShow;
use App\Livewire\Offerings\OfferingIndex;
use App\Livewire\Onboarding\OnboardingWizard;
use App\Livewire\Pledges\CampaignIndex;
use App\Livewire\Pledges\PledgeIndex;
use App\Livewire\PrayerRequests\PrayerRequestIndex;
use App\Livewire\PrayerRequests\PrayerRequestShow;
use App\Livewire\Reports\Attendance\AbsentMembersReport;
use App\Livewire\Reports\Attendance\FirstTimeVisitorsReport;
use App\Livewire\Reports\Attendance\MonthlyAttendanceComparison;
use App\Livewire\Reports\Attendance\ServiceWiseAttendance;
use App\Livewire\Reports\Attendance\WeeklyAttendanceSummary;
use App\Livewire\Reports\Membership\InactiveMembersReport;
use App\Livewire\Reports\Membership\MemberDemographics;
use App\Livewire\Reports\Membership\MemberDirectory;
use App\Livewire\Reports\Membership\MemberGrowthTrends;
use App\Livewire\Reports\Membership\NewMembersReport;
use App\Livewire\Reports\ReportCenter;
use App\Livewire\Services\ServiceIndex;
use App\Livewire\Services\ServiceShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Organization;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\PaymentHistory;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Subscription;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Sms\SmsAnalytics;
use App\Livewire\Sms\SmsCompose;
use App\Livewire\Sms\SmsIndex;
use App\Livewire\Sms\SmsTemplateIndex;
use App\Livewire\Upgrade\PlanCheckout;
use App\Livewire\Upgrade\PlansIndex;
use App\Livewire\Users\BranchUserIndex;
use App\Livewire\Visitors\FollowUpQueue;
use App\Livewire\Visitors\FollowUpTemplateIndex;
use App\Livewire\Visitors\VisitorAnalytics;
use App\Livewire\Visitors\VisitorIndex;
use App\Livewire\Visitors\VisitorShow;
use App\Models\Tenant\Branch;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
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

    // Onboarding routes (auth only - flow controlled by custom Fortify responses)
    Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function (): void {
        Route::get('/', OnboardingWizard::class)->name('index');
    });

    Route::get('/', function () {
        return redirect()->route('dashboard');
    })->name('tenant.home');

    // Dashboard
    Route::get('/dashboard', Dashboard::class)
        ->middleware(['auth', 'verified', 'onboarding.complete'])
        ->name('dashboard');

    // Mobile Self Check-in (public access via token)
    Route::get('/checkin/{token}', MobileSelfCheckIn::class)
        ->name('checkin.qr');

    // Public giving page (no auth required)
    Route::get('/branches/{branch}/give', PublicGivingForm::class)
        ->name('giving.form');

    // Branch user invitation acceptance (guest route)
    Route::get('/invitations/{token}/accept', AcceptBranchInvitation::class)
        ->name('invitations.accept')
        ->middleware('guest');

    // Also accessible at /give for convenience
    Route::get('/give', function () {
        // Redirect to the main branch giving page
        $mainBranch = Branch::where('is_main', true)->first();
        if ($mainBranch) {
            return redirect()->route('giving.form', $mainBranch);
        }

        return redirect()->route('dashboard');
    })->name('giving.public');

    // Public event pages (no auth required)
    Route::get('/events/{branch}/{event}', PublicEventDetails::class)
        ->name('events.public.details');
    Route::get('/events/{branch}/{event}/register', PublicEventRegistration::class)
        ->name('events.public.register');
    Route::get('/events/{branch}/{event}/ticket/{registration}', [EventTicketController::class, 'download'])
        ->name('events.public.ticket.download')
        ->middleware('signed');

    // Paystack webhook (no auth, no CSRF)
    Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
        ->name('webhooks.paystack')
        ->withoutMiddleware(['web']);

    // Email tracking routes (no auth required)
    Route::get('/email/track/{emailLog}/pixel.gif', [EmailTrackingController::class, 'pixel'])
        ->name('email.track.pixel');
    Route::get('/email/track/{emailLog}/click', [EmailTrackingController::class, 'click'])
        ->name('email.track.click');

    // Member portal invitation (guest)
    Route::get('/member/invitation/{token}', AcceptMemberInvitation::class)
        ->name('member.invitation.accept')
        ->middleware('guest');

    // Member portal routes (auth + member middleware)
    Route::middleware(['auth', 'member'])->prefix('member')->name('member.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('member.dashboard'))->name('index');
        Route::get('/dashboard', MemberDashboard::class)->name('dashboard');
        Route::get('/profile', MemberProfile::class)->name('profile');
        Route::get('/contact', MemberContactInfo::class)->name('contact');
        Route::get('/password', MemberPassword::class)->name('password');
        Route::get('/two-factor', MemberTwoFactor::class)->name('two-factor');
        Route::get('/appearance', MemberAppearance::class)->name('appearance');
        Route::get('/giving', MemberGiving::class)->name('giving');
        Route::get('/attendance', MemberAttendance::class)->name('attendance');
        Route::get('/pledges', MemberPledges::class)->name('pledges');
        Route::get('/events', MemberEvents::class)->name('events');
        Route::get('/household', MemberHousehold::class)->name('household');
    });

    // Upgrade required page (auth required but no onboarding check)
    Route::middleware(['auth'])->group(function (): void {
        Route::get('/upgrade', function (): Factory|View {
            $module = request('module');
            $moduleName = $module
                ? PlanModule::tryFrom($module)?->label() ?? __('This feature')
                : __('This feature');

            return view('upgrade-required', ['moduleName' => $moduleName]);
        })->name('upgrade.required');

        // Plan upgrade routes
        Route::get('/plans', PlansIndex::class)
            ->name('plans.index');
        Route::get('/plans/{plan}/checkout', PlanCheckout::class)
            ->name('plans.checkout');
    });

    // Authenticated routes (require completed onboarding)
    Route::middleware(['auth', 'onboarding.complete'])->group(function (): void {
        // Settings (no module restriction)
        Route::redirect('settings', 'settings/profile');
        Route::get('settings/profile', Profile::class)->name('profile.edit');
        Route::get('settings/password', Password::class)->name('user-password.edit');
        Route::get('settings/two-factor', TwoFactor::class)->name('two-factor.show');
        Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
        Route::get('settings/organization', Organization::class)->name('organization.edit');
        Route::get('settings/subscription', Subscription::class)->name('subscription.show');
        Route::get('settings/payments', PaymentHistory::class)->name('payments.history');

        // Invoice PDF Download
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])
            ->name('invoices.download');

        // Branch Management (no module restriction - core feature)
        Route::get('/branches', BranchIndex::class)
            ->name('branches.index');
        Route::get('/branches/{branch}/settings', BranchSettings::class)
            ->name('branches.settings');

        // Branch User Management (no module restriction - core feature)
        Route::get('/branches/{branch}/users', BranchUserIndex::class)
            ->name('branches.users.index');

        // Activity Logs (no module restriction - core feature)
        Route::get('/branches/{branch}/activity-logs', ActivityLogIndex::class)
            ->name('activity-logs.index');

        // Member Management (requires members module)
        Route::middleware(['module:members'])->group(function (): void {
            Route::get('/branches/{branch}/members', MemberIndex::class)
                ->name('members.index');
            Route::get('/branches/{branch}/members/cards-print', MemberCardsBulkPrint::class)
                ->name('members.cards-print');
            Route::get('/branches/{branch}/members/{member}/qr-print', MemberQrPrint::class)
                ->name('members.qr-print');
            Route::get('/branches/{branch}/members/{member}', MemberShow::class)
                ->name('members.show');
        });

        // Cluster Management (requires clusters module)
        Route::middleware(['module:clusters'])->group(function (): void {
            Route::get('/branches/{branch}/clusters', ClusterIndex::class)
                ->name('clusters.index');
            Route::get('/branches/{branch}/clusters/{cluster}', ClusterShow::class)
                ->name('clusters.show');
        });

        // Service Management (requires services module)
        Route::middleware(['module:services'])->group(function (): void {
            Route::get('/branches/{branch}/services', ServiceIndex::class)
                ->name('services.index');
            Route::get('/branches/{branch}/services/{service}', ServiceShow::class)
                ->name('services.show');
        });

        // Attendance Management (requires attendance module)
        Route::middleware(['module:attendance'])->group(function (): void {
            Route::get('/branches/{branch}/attendance', AttendanceIndex::class)
                ->name('attendance.index');
            Route::get('/branches/{branch}/attendance/analytics', AttendanceAnalytics::class)
                ->name('attendance.analytics');
            Route::get('/branches/{branch}/services/{service}/check-in', LiveCheckIn::class)
                ->name('attendance.live-check-in');
            Route::get('/branches/{branch}/services/{service}/dashboard', AttendanceDashboard::class)
                ->name('attendance.dashboard');
            Route::get('/branches/{branch}/services/{service}/children', ChildrenCheckIn::class)
                ->name('attendance.children');
        });

        // Household Management (requires households module)
        Route::middleware(['module:households'])->group(function (): void {
            Route::get('/branches/{branch}/households', HouseholdIndex::class)
                ->name('households.index');
            Route::get('/branches/{branch}/households/{household}', HouseholdShow::class)
                ->name('households.show');
        });

        // Visitor Management (requires visitors module)
        Route::middleware(['module:visitors'])->group(function (): void {
            Route::get('/branches/{branch}/visitors', VisitorIndex::class)
                ->name('visitors.index');
            Route::get('/branches/{branch}/visitors/analytics', VisitorAnalytics::class)
                ->name('visitors.analytics');
            Route::get('/branches/{branch}/visitors/follow-ups', FollowUpQueue::class)
                ->name('visitors.follow-ups');
            Route::get('/branches/{branch}/visitors/follow-up-templates', FollowUpTemplateIndex::class)
                ->name('visitors.follow-up-templates');
            Route::get('/branches/{branch}/visitors/{visitor}', VisitorShow::class)
                ->name('visitors.show');
        });

        // Donations Management (requires donations module)
        Route::middleware(['module:donations'])->group(function (): void {
            Route::get('/branches/{branch}/finance/dashboard', FinanceDashboard::class)
                ->name('finance.dashboard');
            Route::get('/branches/{branch}/finance/donor-engagement', DonorEngagement::class)
                ->name('finance.donor-engagement');
            Route::get('/branches/{branch}/donations', DonationIndex::class)
                ->name('donations.index');
            Route::get('/branches/{branch}/offerings', OfferingIndex::class)
                ->name('offerings.index');
            Route::get('/branches/{branch}/my-giving', MemberGivingHistory::class)
                ->name('giving.history');
        });

        // Expenses Management (requires expenses module)
        Route::middleware(['module:expenses'])->group(function (): void {
            Route::get('/branches/{branch}/expenses', ExpenseIndex::class)
                ->name('expenses.index');
            Route::get('/branches/{branch}/expenses/recurring', RecurringExpenseIndex::class)
                ->name('expenses.recurring');
        });

        // Pledges Management (requires pledges module)
        Route::middleware(['module:pledges'])->group(function (): void {
            Route::get('/branches/{branch}/pledges', PledgeIndex::class)
                ->name('pledges.index');
            Route::get('/branches/{branch}/campaigns', CampaignIndex::class)
                ->name('campaigns.index');
        });

        // Budgets Management (requires budgets module)
        Route::middleware(['module:budgets'])->group(function (): void {
            Route::get('/branches/{branch}/budgets', BudgetIndex::class)
                ->name('budgets.index');
            Route::get('/branches/{branch}/finance/reports', FinanceReports::class)
                ->name('finance.reports');
        });

        // SMS Management (requires sms module)
        Route::middleware(['module:sms'])->group(function (): void {
            Route::get('/branches/{branch}/sms', SmsIndex::class)
                ->name('sms.index');
            Route::get('/branches/{branch}/sms/compose', SmsCompose::class)
                ->name('sms.compose');
            Route::get('/branches/{branch}/sms/templates', SmsTemplateIndex::class)
                ->name('sms.templates');
            Route::get('/branches/{branch}/sms/analytics', SmsAnalytics::class)
                ->name('sms.analytics');
        });

        // Email Management (requires email module)
        Route::middleware(['module:email'])->group(function (): void {
            Route::get('/branches/{branch}/email', EmailIndex::class)
                ->name('email.index');
            Route::get('/branches/{branch}/email/compose', EmailCompose::class)
                ->name('email.compose');
            Route::get('/branches/{branch}/email/templates', EmailTemplateIndex::class)
                ->name('email.templates');
            Route::get('/branches/{branch}/email/analytics', EmailAnalytics::class)
                ->name('email.analytics');
        });

        // Equipment Management (requires equipment module)
        Route::middleware(['module:equipment'])->group(function (): void {
            Route::get('/branches/{branch}/equipment', EquipmentIndex::class)
                ->name('equipment.index');
            Route::get('/branches/{branch}/equipment/{equipment}', EquipmentShow::class)
                ->name('equipment.show');
        });

        // Prayer Request Management (requires prayer_requests module)
        Route::middleware(['module:prayer_requests'])->group(function (): void {
            Route::get('/branches/{branch}/prayer-requests', PrayerRequestIndex::class)
                ->name('prayer-requests.index');
            Route::get('/branches/{branch}/prayer-requests/{prayerRequest}', PrayerRequestShow::class)
                ->name('prayer-requests.show');
        });

        // Duty Roster Management (requires duty_roster module)
        Route::middleware(['module:duty_roster'])->group(function (): void {
            Route::get('/branches/{branch}/duty-rosters', DutyRosterIndex::class)
                ->name('duty-rosters.index');
            Route::get('/branches/{branch}/duty-rosters/pools', DutyRosterPoolIndex::class)
                ->name('duty-rosters.pools.index');
            Route::get('/branches/{branch}/duty-rosters/availability', MemberAvailabilityIndex::class)
                ->name('duty-rosters.availability.index');
            Route::get('/branches/{branch}/duty-rosters/generate', DutyRosterGenerationWizard::class)
                ->name('duty-rosters.generate');
            Route::get('/branches/{branch}/duty-rosters/print', DutyRosterPrint::class)
                ->name('duty-rosters.print');
            Route::get('/branches/{branch}/duty-rosters/{dutyRoster}', DutyRosterShow::class)
                ->name('duty-rosters.show');
        });

        // Children's Ministry Management (requires children module)
        Route::middleware(['module:children'])->group(function (): void {
            Route::get('/branches/{branch}/children', ChildrenDirectory::class)
                ->name('children.index');
            Route::get('/branches/{branch}/children/dashboard', ChildrenDashboard::class)
                ->name('children.dashboard');
            Route::get('/branches/{branch}/children/age-groups', AgeGroupIndex::class)
                ->name('children.age-groups');
        });

        // AI Insights Dashboard (requires ai_insights module)
        Route::middleware(['module:ai_insights'])->group(function (): void {
            Route::get('/branches/{branch}/ai-insights', InsightsDashboard::class)
                ->name('ai-insights.dashboard');
            Route::get('/branches/{branch}/ai-insights/settings', AlertSettings::class)
                ->name('branches.alert-settings');
            Route::get('/branches/{branch}/ai-insights/giving', GivingIntelligenceDashboard::class)
                ->name('ai-insights.giving');
            Route::get('/branches/{branch}/ai-insights/chatbot', ConversationMonitor::class)
                ->name('ai-insights.chatbot');
        });

        // Event Management (requires events module)
        Route::middleware(['module:events'])->group(function (): void {
            Route::get('/branches/{branch}/events', EventIndex::class)
                ->name('events.index');
            Route::get('/branches/{branch}/events/{event}', EventShow::class)
                ->name('events.show');
            Route::get('/branches/{branch}/events/{event}/registrations', EventShow::class)
                ->name('events.registrations');
            Route::get('/branches/{branch}/events/{event}/check-in', EventCheckIn::class)
                ->name('events.check-in');
            Route::get('/branches/{branch}/events/{event}/predictions', EventAttendancePredictions::class)
                ->name('events.predictions');
        });

        // Report Center (requires reports module)
        Route::middleware(['module:reports'])->group(function (): void {
            Route::get('/branches/{branch}/reports', ReportCenter::class)
                ->name('reports.index');

            // Membership Reports
            Route::get('/branches/{branch}/reports/membership/directory', MemberDirectory::class)
                ->name('reports.membership.directory');
            Route::get('/branches/{branch}/reports/membership/new-members', NewMembersReport::class)
                ->name('reports.membership.new-members');
            Route::get('/branches/{branch}/reports/membership/inactive', InactiveMembersReport::class)
                ->name('reports.membership.inactive');
            Route::get('/branches/{branch}/reports/membership/demographics', MemberDemographics::class)
                ->name('reports.membership.demographics');
            Route::get('/branches/{branch}/reports/membership/growth', MemberGrowthTrends::class)
                ->name('reports.membership.growth');

            // Attendance Reports
            Route::get('/branches/{branch}/reports/attendance/weekly', WeeklyAttendanceSummary::class)
                ->name('reports.attendance.weekly');
            Route::get('/branches/{branch}/reports/attendance/monthly', MonthlyAttendanceComparison::class)
                ->name('reports.attendance.monthly');
            Route::get('/branches/{branch}/reports/attendance/by-service', ServiceWiseAttendance::class)
                ->name('reports.attendance.by-service');
            Route::get('/branches/{branch}/reports/attendance/absent-members', AbsentMembersReport::class)
                ->name('reports.attendance.absent-members');
            Route::get('/branches/{branch}/reports/attendance/visitors', FirstTimeVisitorsReport::class)
                ->name('reports.attendance.visitors');
        });
    });
});
