<?php

declare(strict_types=1);

namespace App\Livewire\Attendance;

use App\Enums\MembershipStatus;
use App\Enums\PlanModule;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceForecast as AttendanceForecastModel;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\AI\AttendanceForecastService;
use App\Services\PlanAccessService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AttendanceAnalytics extends Component
{
    use WithPagination;

    public Branch $branch;

    #[Url]
    public int $period = 90;

    #[Url]
    public ?string $serviceFilter = null;

    #[Url]
    public string $memberSearch = '';

    #[Url]
    public string $memberSortBy = 'engagement_score';

    #[Url]
    public string $memberSortDirection = 'desc';

    public int $lapsedWeeksThreshold = 4;

    public function mount(Branch $branch): void
    {
        $this->authorize('viewAny', [Attendance::class, $branch]);
        $this->branch = $branch;
    }

    public function setPeriod(int $days): void
    {
        $this->period = $days;
        $this->resetPage();
    }

    public function updatedServiceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMemberSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->memberSortBy === $column) {
            $this->memberSortDirection = $this->memberSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->memberSortBy = $column;
            $this->memberSortDirection = 'desc';
        }
        $this->resetPage();
    }

    // ============================================
    // PERIOD HELPERS
    // ============================================

    private function getCurrentPeriodStart(): Carbon
    {
        return now()->subDays($this->period)->startOfDay();
    }

    private function getCurrentPeriodEnd(): Carbon
    {
        return now()->endOfDay();
    }

    private function getPreviousPeriodStart(): Carbon
    {
        return $this->getCurrentPeriodStart()->copy()->subDays($this->period)->startOfDay();
    }

    private function getPreviousPeriodEnd(): Carbon
    {
        return $this->getCurrentPeriodStart()->copy()->subDay()->endOfDay();
    }

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Attendance::where('branch_id', $this->branch->id);

        if ($this->serviceFilter) {
            $query->where('service_id', $this->serviceFilter);
        }

        return $query;
    }

    // ============================================
    // EXECUTIVE SUMMARY STATS
    // ============================================

    #[Computed]
    public function summaryStats(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();

        // Current period stats
        $totalAttendance = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->count();

        $uniqueMembers = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->whereNotNull('member_id')
            ->distinct('member_id')
            ->count('member_id');

        $totalVisitors = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->whereNotNull('visitor_id')
            ->distinct('visitor_id')
            ->count('visitor_id');

        // Service dates for average calculation
        $serviceDates = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->distinct('date')
            ->count('date');

        $avgPerService = $serviceDates > 0
            ? round($totalAttendance / $serviceDates, 1)
            : 0;

        // Previous period stats for comparison
        $previousTotal = $this->baseQuery()
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->count();

        $previousUniqueMembers = $this->baseQuery()
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->whereNotNull('member_id')
            ->distinct('member_id')
            ->count('member_id');

        // Calculate growth percentages
        $totalGrowth = $previousTotal > 0
            ? round((($totalAttendance - $previousTotal) / $previousTotal) * 100, 1)
            : ($totalAttendance > 0 ? 100 : 0);

        $memberGrowth = $previousUniqueMembers > 0
            ? round((($uniqueMembers - $previousUniqueMembers) / $previousUniqueMembers) * 100, 1)
            : ($uniqueMembers > 0 ? 100 : 0);

        return [
            'total_attendance' => $totalAttendance,
            'previous_total' => $previousTotal,
            'total_growth' => $totalGrowth,
            'unique_members' => $uniqueMembers,
            'previous_unique_members' => $previousUniqueMembers,
            'member_growth' => $memberGrowth,
            'total_visitors' => $totalVisitors,
            'avg_per_service' => $avgPerService,
            'service_dates' => $serviceDates,
        ];
    }

    // ============================================
    // MEMBER ENGAGEMENT METRICS
    // ============================================

    #[Computed]
    public function engagementMetrics(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();
        $lapsedDate = now()->subWeeks($this->lapsedWeeksThreshold);

        // Get total service dates for engagement calculation
        $totalServiceDates = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->distinct('date')
            ->count('date');

        if ($totalServiceDates === 0) {
            return [
                'regular_count' => 0,
                'casual_count' => 0,
                'at_risk_count' => 0,
                'lapsed_count' => 0,
                'total_active_members' => 0,
            ];
        }

        // Get attendance counts per member in current period
        $memberAttendance = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->whereNotNull('member_id')
            ->selectRaw('member_id, COUNT(DISTINCT date) as attendance_count')
            ->groupBy('member_id')
            ->get()
            ->keyBy('member_id');

        // Get attendance counts per member in previous period
        $previousAttendance = $this->baseQuery()
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->whereNotNull('member_id')
            ->selectRaw('member_id, COUNT(DISTINCT date) as attendance_count')
            ->groupBy('member_id')
            ->get()
            ->keyBy('member_id');

        $previousServiceDates = $this->baseQuery()
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->distinct('date')
            ->count('date');

        $regularCount = 0;
        $casualCount = 0;
        $atRiskCount = 0;

        foreach ($memberAttendance as $memberId => $data) {
            $currentScore = ($data->attendance_count / $totalServiceDates) * 100;

            if ($currentScore >= 75) {
                $regularCount++;
            } elseif ($currentScore >= 25) {
                $casualCount++;
            }

            // Check for at-risk (was regular in previous period, now below 50%)
            if ($previousServiceDates > 0 && isset($previousAttendance[$memberId])) {
                $previousScore = ($previousAttendance[$memberId]->attendance_count / $previousServiceDates) * 100;
                if ($previousScore >= 75 && $currentScore < 50) {
                    $atRiskCount++;
                }
            }
        }

        // Lapsed members: attended before but not in last X weeks
        $lapsedCount = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereHas('attendance', function ($q) use ($lapsedDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('date', '<', $lapsedDate);
            })
            ->whereDoesntHave('attendance', function ($q) use ($lapsedDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('date', '>=', $lapsedDate);
            })
            ->count();

        return [
            'regular_count' => $regularCount,
            'casual_count' => $casualCount,
            'at_risk_count' => $atRiskCount,
            'lapsed_count' => $lapsedCount,
            'total_active_members' => $memberAttendance->count(),
        ];
    }

    // ============================================
    // ATTENDANCE TREND DATA
    // ============================================

    #[Computed]
    public function attendanceTrendData(): array
    {
        $labels = [];
        $currentYearData = [];
        $previousYearData = [];

        // Last 12 weeks
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $labels[] = $weekStart->format('M d');

            $count = $this->baseQuery()
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->count();

            $currentYearData[] = $count;

            // Same week last year
            $prevWeekStart = $weekStart->copy()->subYear();
            $prevWeekEnd = $weekEnd->copy()->subYear();

            $prevCount = $this->baseQuery()
                ->whereBetween('date', [$prevWeekStart, $prevWeekEnd])
                ->count();

            $previousYearData[] = $prevCount;
        }

        return [
            'labels' => $labels,
            'current_year' => $currentYearData,
            'previous_year' => $previousYearData,
        ];
    }

    // ============================================
    // SERVICE UTILIZATION
    // ============================================

    #[Computed]
    public function serviceUtilization(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        $services = Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->get();

        $utilization = [];

        foreach ($services as $service) {
            $query = Attendance::where('branch_id', $this->branch->id)
                ->where('service_id', $service->id)
                ->whereBetween('date', [$currentStart, $currentEnd]);

            $totalAttendance = $query->count();

            $serviceDates = $query->distinct('date')->count('date');

            $avgAttendance = $serviceDates > 0 ? round($totalAttendance / $serviceDates, 1) : 0;

            $capacityPercent = $service->capacity > 0
                ? round(($avgAttendance / $service->capacity) * 100, 1)
                : 0;

            $utilization[] = [
                'id' => $service->id,
                'name' => $service->name,
                'total_attendance' => $totalAttendance,
                'service_dates' => $serviceDates,
                'avg_attendance' => $avgAttendance,
                'capacity' => $service->capacity ?? 0,
                'capacity_percent' => min($capacityPercent, 100),
            ];
        }

        // Sort by total attendance desc
        usort($utilization, fn (array $a, array $b): int => $b['total_attendance'] <=> $a['total_attendance']);

        return $utilization;
    }

    // ============================================
    // VISITOR CONVERSION RATE
    // ============================================

    #[Computed]
    public function visitorConversionRate(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        // Get unique visitors in period
        $visitorIds = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->whereNotNull('visitor_id')
            ->distinct()
            ->pluck('visitor_id');

        $totalVisitors = $visitorIds->count();

        if ($totalVisitors === 0) {
            return [
                'total_visitors' => 0,
                'returning_visitors' => 0,
                'converted_to_member' => 0,
                'conversion_rate' => 0,
            ];
        }

        // N+1 fix: Get visit counts for all visitors in single query
        $visitCounts = Attendance::where('branch_id', $this->branch->id)
            ->whereIn('visitor_id', $visitorIds)
            ->selectRaw('visitor_id, COUNT(DISTINCT date) as visit_count')
            ->groupBy('visitor_id')
            ->pluck('visit_count', 'visitor_id');

        // Count visitors who came more than once
        $returningVisitors = $visitCounts->filter(fn ($count): bool => $count > 1)->count();

        // Visitors converted to members
        $convertedToMember = Visitor::whereIn('id', $visitorIds)
            ->whereNotNull('converted_member_id')
            ->count();

        $conversionRate = $totalVisitors > 0
            ? round((($returningVisitors + $convertedToMember) / $totalVisitors) * 100, 1)
            : 0;

        return [
            'total_visitors' => $totalVisitors,
            'returning_visitors' => $returningVisitors,
            'converted_to_member' => $convertedToMember,
            'conversion_rate' => $conversionRate,
        ];
    }

    // ============================================
    // ENGAGEMENT ALERTS
    // ============================================

    #[Computed]
    public function engagementAlerts(): array
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();
        $previousStart = $this->getPreviousPeriodStart();
        $previousEnd = $this->getPreviousPeriodEnd();
        $lapsedDate = now()->subWeeks($this->lapsedWeeksThreshold);

        // Lapsed members
        $lapsedMembers = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->whereHas('attendance', function ($q) use ($lapsedDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('date', '<', $lapsedDate);
            })
            ->whereDoesntHave('attendance', function ($q) use ($lapsedDate): void {
                $q->where('branch_id', $this->branch->id)
                    ->where('date', '>=', $lapsedDate);
            })
            ->with(['attendance' => function ($q): void {
                $q->where('branch_id', $this->branch->id)
                    ->orderByDesc('date')
                    ->limit(1);
            }])
            ->limit(10)
            ->get()
            ->map(function ($member): array {
                $lastAttendance = $member->attendance->first();

                return [
                    'id' => $member->id,
                    'name' => $member->fullName(),
                    'photo_url' => $member->photo_url,
                    'last_attendance' => $lastAttendance?->date?->format('M d, Y'),
                    'days_since' => $lastAttendance?->date ? (int) $lastAttendance->date->diffInDays(now()) : null,
                ];
            });

        // At-risk members (declining attendance)
        $totalServiceDates = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->distinct('date')
            ->count('date');

        $previousServiceDates = $this->baseQuery()
            ->whereBetween('date', [$previousStart, $previousEnd])
            ->distinct('date')
            ->count('date');

        $atRiskMembers = collect();

        if ($totalServiceDates > 0 && $previousServiceDates > 0) {
            $currentAttendance = $this->baseQuery()
                ->whereBetween('date', [$currentStart, $currentEnd])
                ->whereNotNull('member_id')
                ->selectRaw('member_id, COUNT(DISTINCT date) as attendance_count')
                ->groupBy('member_id')
                ->get()
                ->keyBy('member_id');

            $previousAttendance = $this->baseQuery()
                ->whereBetween('date', [$previousStart, $previousEnd])
                ->whereNotNull('member_id')
                ->selectRaw('member_id, COUNT(DISTINCT date) as attendance_count')
                ->groupBy('member_id')
                ->get()
                ->keyBy('member_id');

            // N+1 fix: Collect at-risk member IDs first, then batch load
            $atRiskData = collect();
            foreach ($previousAttendance as $memberId => $prevData) {
                $previousScore = ($prevData->attendance_count / $previousServiceDates) * 100;

                if ($previousScore >= 75) {
                    $currentData = $currentAttendance->get($memberId);
                    $currentScore = $currentData
                        ? ($currentData->attendance_count / $totalServiceDates) * 100
                        : 0;

                    if ($currentScore < 50) {
                        $atRiskData->put($memberId, [
                            'previous_score' => round($previousScore, 1),
                            'current_score' => round($currentScore, 1),
                            'change' => round($currentScore - $previousScore, 1),
                        ]);
                    }
                }
            }

            // Batch load all at-risk members in a single query
            if ($atRiskData->isNotEmpty()) {
                $members = Member::whereIn('id', $atRiskData->keys())->get()->keyBy('id');
                foreach ($atRiskData as $memberId => $scores) {
                    $member = $members->get($memberId);
                    if ($member) {
                        $atRiskMembers->push([
                            'id' => $member->id,
                            'name' => $member->fullName(),
                            'photo_url' => $member->photo_url,
                            'previous_score' => $scores['previous_score'],
                            'current_score' => $scores['current_score'],
                            'change' => $scores['change'],
                        ]);
                    }
                }
            }
        }

        // First-time visitors not returning (within 30 days)
        // N+1 fix: Use withCount to filter single-visit visitors in database
        $thirtyDaysAgo = now()->subDays(30);
        $notReturningVisitors = Visitor::whereHas('attendance', function ($q) use ($thirtyDaysAgo, $currentEnd): void {
            $q->where('branch_id', $this->branch->id)
                ->whereBetween('date', [$thirtyDaysAgo, $currentEnd]);
        })
            ->whereNull('converted_member_id')
            ->withCount(['attendance as total_visit_count' => function ($q): void {
                $q->where('branch_id', $this->branch->id);
            }])
            ->having('total_visit_count', '=', 1)
            ->limit(10)
            ->get()
            ->map(fn ($visitor): array => [
                'id' => $visitor->id,
                'name' => $visitor->fullName(),
                'visit_date' => $visitor->visit_date?->format('M d, Y'),
                'phone' => $visitor->phone,
            ])
            ->values();

        return [
            'lapsed' => $lapsedMembers,
            'at_risk' => $atRiskMembers->take(10),
            'not_returning_visitors' => $notReturningVisitors,
            'lapsed_count' => $lapsedMembers->count(),
            'at_risk_count' => $atRiskMembers->count(),
            'not_returning_count' => $notReturningVisitors->count(),
        ];
    }

    // ============================================
    // MEMBER ENGAGEMENT LIST
    // ============================================

    #[Computed]
    public function memberEngagementList(): LengthAwarePaginator
    {
        $currentStart = $this->getCurrentPeriodStart();
        $currentEnd = $this->getCurrentPeriodEnd();

        $totalServiceDates = $this->baseQuery()
            ->whereBetween('date', [$currentStart, $currentEnd])
            ->distinct('date')
            ->count('date');

        // Get all members with attendance counts in single query (N+1 fix)
        $membersQuery = Member::where('primary_branch_id', $this->branch->id)
            ->where('status', MembershipStatus::Active)
            ->withCount(['attendance as period_attendance_count' => function ($q) use ($currentStart, $currentEnd): void {
                $q->where('branch_id', $this->branch->id)
                    ->whereBetween('date', [$currentStart, $currentEnd]);
            }])
            ->addSelect([
                'last_attendance_date' => Attendance::selectRaw('MAX(date)')
                    ->whereColumn('member_id', 'members.id')
                    ->where('branch_id', $this->branch->id),
            ]);

        if ($this->memberSearch !== '' && $this->memberSearch !== '0') {
            $membersQuery->where(function ($q): void {
                $q->where('first_name', 'like', '%'.$this->memberSearch.'%')
                    ->orWhere('last_name', 'like', '%'.$this->memberSearch.'%');
            });
        }

        $members = $membersQuery->get()->map(function ($member) use ($totalServiceDates) {
            $attendanceCount = $member->period_attendance_count ?? 0;
            $lastAttendanceDate = $member->last_attendance_date ? Carbon::parse($member->last_attendance_date) : null;

            $engagementScore = $totalServiceDates > 0
                ? round(($attendanceCount / $totalServiceDates) * 100, 1)
                : 0;

            return (object) [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'photo_url' => $member->photo_url,
                'attendance_count' => $attendanceCount,
                'engagement_score' => $engagementScore,
                'last_attendance' => $lastAttendanceDate,
                'days_since' => $lastAttendanceDate ? (int) $lastAttendanceDate->diffInDays(now()) : null,
            ];
        });

        // Filter out members with zero attendance if needed
        $members = $members->filter(fn ($m): bool => $m->attendance_count > 0 || $m->last_attendance !== null);

        // Apply sorting
        $sorted = match ($this->memberSortBy) {
            'engagement_score' => $members->sortBy('engagement_score', SORT_REGULAR, $this->memberSortDirection === 'desc'),
            'attendance_count' => $members->sortBy('attendance_count', SORT_REGULAR, $this->memberSortDirection === 'desc'),
            'last_attendance' => $members->sortBy(fn ($m) => $m->last_attendance?->timestamp ?? 0, SORT_REGULAR, $this->memberSortDirection === 'desc'),
            'name' => $members->sortBy(fn ($m): string => $m->first_name.' '.$m->last_name, SORT_REGULAR, $this->memberSortDirection === 'desc'),
            default => $members->sortBy('engagement_score', SORT_REGULAR, true),
        };

        // Manual pagination
        $page = $this->getPage();
        $perPage = 15;
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // ============================================
    // SERVICES LIST FOR FILTER
    // ============================================

    #[Computed]
    public function services(): \Illuminate\Database\Eloquent\Collection
    {
        return Service::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    // ============================================
    // ATTENDANCE FORECASTING
    // ============================================

    #[Computed]
    public function forecastEnabled(): bool
    {
        return app(PlanAccessService::class)->hasModule(PlanModule::AiInsights)
            && config('ai.features.attendance_forecast.enabled', false);
    }

    #[Computed]
    public function upcomingForecasts(): Collection
    {
        if (! $this->forecastEnabled) {
            return collect();
        }

        return AttendanceForecastModel::where('branch_id', $this->branch->id)
            ->where('forecast_date', '>=', today())
            ->where('forecast_date', '<=', today()->addWeeks(4))
            ->orderBy('forecast_date')
            ->with('service')
            ->get()
            ->groupBy(fn ($forecast) => $forecast->forecast_date->format('Y-m-d'))
            ->map(function ($forecasts, \DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $date): array {
                return [
                    'date' => Carbon::parse($date),
                    'forecasts' => $forecasts->map(fn ($f): array => [
                        'service_name' => $f->service->name,
                        'predicted_attendance' => $f->predicted_attendance,
                        'predicted_members' => $f->predicted_members,
                        'predicted_visitors' => $f->predicted_visitors,
                        'confidence' => $f->confidence_score,
                        'confidence_level' => $f->confidenceLevel(),
                        'confidence_color' => $f->confidenceBadgeColor(),
                    ]),
                    'total_predicted' => $forecasts->sum('predicted_attendance'),
                ];
            })
            ->values();
    }

    #[Computed]
    public function forecastAccuracy(): ?float
    {
        if (! $this->forecastEnabled) {
            return null;
        }

        return app(AttendanceForecastService::class)->calculateAccuracy($this->branch->id, 30);
    }

    #[Computed]
    public function recentForecastComparison(): Collection
    {
        if (! $this->forecastEnabled) {
            return collect();
        }

        return AttendanceForecastModel::where('branch_id', $this->branch->id)
            ->whereNotNull('actual_attendance')
            ->where('forecast_date', '>=', today()->subDays(30))
            ->orderByDesc('forecast_date')
            ->with('service')
            ->limit(10)
            ->get()
            ->map(fn ($f): array => [
                'date' => $f->forecast_date->format('M d'),
                'service_name' => $f->service->name,
                'predicted' => $f->predicted_attendance,
                'actual' => $f->actual_attendance,
                'variance' => $f->variance,
                'variance_percent' => round($f->variance_percent ?? 0, 1),
                'accurate' => $f->wasAccurate(),
            ]);
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.attendance.attendance-analytics');
    }
}
