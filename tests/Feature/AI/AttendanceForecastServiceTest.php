<?php

declare(strict_types=1);

use App\Models\Tenant\Attendance;
use App\Models\Tenant\AttendanceForecast;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\AI\AiService;
use App\Services\AI\AttendanceForecastService;
use Carbon\Carbon;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->create();

    // Create a service on Sunday (day_of_week = 0)
    $this->testService = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => Carbon::SUNDAY,
        'is_active' => true,
        'capacity' => 100,
    ]);

    $aiService = new AiService;
    $this->service = new AttendanceForecastService($aiService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('generates forecast with base prediction from historical data', function (): void {
    // Create historical attendance on proper Sunday dates
    for ($week = 1; $week <= 8; $week++) {
        // Get the Sunday of X weeks ago
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);

        $members = Member::factory()->count(50)->create(['primary_branch_id' => $this->branch->id]);
        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'date' => $date,
            ]);
        }
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->predictedAttendance)->toBeGreaterThan(0);
    expect($forecast->confidence)->toBeGreaterThanOrEqual(40);
    expect($forecast->confidence)->toBeLessThanOrEqual(95);
    expect($forecast->factors)->toHaveKey('historical_average');
});

it('returns low confidence for limited historical data', function (): void {
    // Only 2 weeks of data
    for ($week = 1; $week <= 2; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        Attendance::factory()->create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->testService->id,
            'date' => $date,
        ]);
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->confidence)->toBeLessThan(70);
    expect($forecast->factors)->toHaveKey('data_quality');
});

it('applies seasonal adjustments for different months', function (): void {
    // Create consistent attendance pattern
    for ($week = 1; $week <= 8; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        $members = Member::factory()->count(100)->create(['primary_branch_id' => $this->branch->id]);

        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'date' => $date,
            ]);
        }
    }

    // Forecast for July (summer low - factor 0.85)
    $julyDate = Carbon::createFromDate(now()->year, 7, 15)->next(Carbon::SUNDAY);
    $julyForecast = $this->service->forecastForService($this->testService, $julyDate);

    // Forecast for December (holiday - factor 1.10)
    $decemberDate = Carbon::createFromDate(now()->year, 12, 15)->next(Carbon::SUNDAY);
    $decemberForecast = $this->service->forecastForService($this->testService, $decemberDate);

    // July should predict lower attendance than December due to seasonal factors
    expect($julyForecast->predictedAttendance)->toBeLessThan($decemberForecast->predictedAttendance);
});

it('calculates capacity percentage when service has capacity', function (): void {
    for ($week = 1; $week <= 4; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        $members = Member::factory()->count(80)->create(['primary_branch_id' => $this->branch->id]);

        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'date' => $date,
            ]);
        }
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->capacityPercent)->not()->toBeNull();
    expect($forecast->capacityPercent)->toBeGreaterThan(0);
});

it('separates member and visitor predictions', function (): void {
    for ($week = 1; $week <= 4; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);

        // Create member attendance
        $members = Member::factory()->count(40)->create(['primary_branch_id' => $this->branch->id]);
        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'visitor_id' => null,
                'date' => $date,
            ]);
        }

        // Create visitor attendance (create actual visitor records)
        $visitors = Visitor::factory()->count(10)->create(['branch_id' => $this->branch->id]);
        foreach ($visitors as $visitor) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => null,
                'visitor_id' => $visitor->id,
                'date' => $date,
            ]);
        }
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->predictedMembers)->toBeGreaterThan(0);
    expect($forecast->predictedVisitors)->toBeGreaterThan(0);
    expect($forecast->predictedAttendance)->toBe($forecast->predictedMembers + $forecast->predictedVisitors);
});

it('generates forecasts for all active services in branch', function (): void {
    // Create additional service on Wednesday
    $midweekService = Service::factory()->create([
        'branch_id' => $this->branch->id,
        'day_of_week' => Carbon::WEDNESDAY,
        'is_active' => true,
    ]);

    // Create attendance for both services
    for ($week = 1; $week <= 4; $week++) {
        $sundayDate = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        $wednesdayDate = now()->subWeeks($week)->next(Carbon::WEDNESDAY);

        Attendance::factory()->create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->testService->id,
            'date' => $sundayDate,
        ]);

        Attendance::factory()->create([
            'branch_id' => $this->branch->id,
            'service_id' => $midweekService->id,
            'date' => $wednesdayDate,
        ]);
    }

    $startDate = now()->startOfWeek();
    $endDate = $startDate->copy()->addWeeks(2);

    $forecasts = $this->service->forecastForBranch($this->branch->id, $startDate, $endDate);

    expect($forecasts)->not()->toBeEmpty();
});

it('updates forecasts in database', function (): void {
    for ($week = 1; $week <= 4; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        Attendance::factory()->create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->testService->id,
            'date' => $date,
        ]);
    }

    $count = $this->service->updateForecastsForBranch($this->branch->id, 2);

    expect($count)->toBeGreaterThan(0);
    expect(AttendanceForecast::where('branch_id', $this->branch->id)->count())->toBe($count);
});

it('records actual attendance and calculates accuracy', function (): void {
    $forecastDate = now()->subDays(3);

    AttendanceForecast::create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->testService->id,
        'forecast_date' => $forecastDate,
        'predicted_attendance' => 100,
        'predicted_members' => 85,
        'predicted_visitors' => 15,
        'confidence_score' => 75,
        'factors' => [],
    ]);

    $result = $this->service->recordActualAttendance(
        $this->testService->id,
        $forecastDate,
        95
    );

    expect($result)->toBeTrue();

    $forecast = AttendanceForecast::where('service_id', $this->testService->id)
        ->where('forecast_date', $forecastDate->toDateString())
        ->first();

    expect($forecast->actual_attendance)->toBe(95);
    expect($forecast->accuracy)->toBeGreaterThan(90);
});

it('calculates branch accuracy from multiple forecasts', function (): void {
    // Create forecasts with actuals
    AttendanceForecast::create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->testService->id,
        'forecast_date' => now()->subDays(7),
        'predicted_attendance' => 100,
        'predicted_members' => 85,
        'predicted_visitors' => 15,
        'confidence_score' => 75,
        'factors' => [],
        'actual_attendance' => 95, // 95% accuracy
    ]);

    AttendanceForecast::create([
        'branch_id' => $this->branch->id,
        'service_id' => $this->testService->id,
        'forecast_date' => now()->subDays(14),
        'predicted_attendance' => 100,
        'predicted_members' => 85,
        'predicted_visitors' => 15,
        'confidence_score' => 75,
        'factors' => [],
        'actual_attendance' => 90, // 90% accuracy
    ]);

    $accuracy = $this->service->calculateAccuracy($this->branch->id, 30);

    expect($accuracy)->not()->toBeNull();
    expect($accuracy)->toBeGreaterThan(85);
});

it('returns null accuracy when no forecasts with actuals exist', function (): void {
    $accuracy = $this->service->calculateAccuracy($this->branch->id, 30);

    expect($accuracy)->toBeNull();
});

it('detects growing trend from historical data', function (): void {
    // Create attendance pattern with clear upward trend
    // Older weeks (further back) have lower attendance
    // More recent weeks have higher attendance
    for ($week = 8; $week >= 1; $week--) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        // Week 8 ago = 50, Week 1 ago = 120 (clear growth)
        $attendanceCount = 50 + (8 - $week) * 10;

        $members = Member::factory()->count($attendanceCount)->create(['primary_branch_id' => $this->branch->id]);

        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'date' => $date,
            ]);
        }
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->factors)->toHaveKey('trend');
    expect($forecast->factors['trend']['value'])->toBeGreaterThan(0);
});

it('uses DTO confidence level helpers correctly', function (): void {
    for ($week = 1; $week <= 8; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        Attendance::factory()->count(100)->create([
            'branch_id' => $this->branch->id,
            'service_id' => $this->testService->id,
            'date' => $date,
        ]);
    }

    $forecastDate = now()->next(Carbon::SUNDAY);
    $forecast = $this->service->forecastForService($this->testService, $forecastDate);

    expect($forecast->confidenceLevel())->toBeIn(['low', 'medium', 'high']);
    expect($forecast->confidenceBadgeColor())->toBeIn(['zinc', 'yellow', 'green']);
});

it('respects feature enabled flag', function (): void {
    config(['ai.features.attendance_forecast.enabled' => false]);
    expect($this->service->isEnabled())->toBeFalse();

    config(['ai.features.attendance_forecast.enabled' => true]);
    expect($this->service->isEnabled())->toBeTrue();
});

it('updates service forecast columns after generating forecasts', function (): void {
    // Create more historical data to ensure reliable forecasts
    for ($week = 1; $week <= 8; $week++) {
        $date = now()->subWeeks($week)->startOfWeek(Carbon::SUNDAY);
        $members = Member::factory()->count(50)->create(['primary_branch_id' => $this->branch->id]);

        foreach ($members as $member) {
            Attendance::factory()->create([
                'branch_id' => $this->branch->id,
                'service_id' => $this->testService->id,
                'member_id' => $member->id,
                'date' => $date,
            ]);
        }
    }

    $count = $this->service->updateForecastsForBranch($this->branch->id, 4);

    // Ensure forecasts were created
    expect($count)->toBeGreaterThan(0);

    $this->testService->refresh();

    // Check if forecast columns were updated
    expect($this->testService->forecast_next_attendance)->not()->toBeNull();
    expect($this->testService->forecast_confidence)->not()->toBeNull();
    expect($this->testService->forecast_calculated_at)->not()->toBeNull();
});
