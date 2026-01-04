<?php

use App\Enums\BranchRole;
use App\Livewire\Reports\Attendance\FirstTimeVisitorsReport;
use App\Livewire\Reports\Attendance\MonthlyAttendanceComparison;
use App\Livewire\Reports\Attendance\WeeklyAttendanceSummary;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create(['name' => 'Test Church']);
    $this->tenant->domains()->create(['domain' => 'test.localhost']);
    tenancy()->initialize($this->tenant);
    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    config(['app.url' => 'http://test.localhost']);
    url()->forceRootUrl('http://test.localhost');
    $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);

    $this->branch = Branch::factory()->main()->create();

    $this->adminUser = User::factory()->create();
    $this->adminUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Admin->value,
    ]);

    $this->volunteerUser = User::factory()->create();
    $this->volunteerUser->branchAccess()->create([
        'branch_id' => $this->branch->id,
        'role' => BranchRole::Volunteer->value,
    ]);
});

afterEach(function () {
    tenancy()->end();
    $this->tenant?->delete();
});

// Weekly Attendance Summary Tests

test('admin can access weekly attendance summary', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.attendance.weekly', $this->branch))
        ->assertStatus(200);
});

test('volunteer cannot access weekly attendance summary', function () {
    $this->actingAs($this->volunteerUser)
        ->get(route('reports.attendance.weekly', $this->branch))
        ->assertForbidden();
});

test('weekly summary can navigate weeks', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(WeeklyAttendanceSummary::class, ['branch' => $this->branch]);

    $initialWeek = $component->get('weekStart');

    $component->call('previousWeek');
    expect($component->get('weekStart'))->not->toBe($initialWeek);

    $component->call('nextWeek');
    expect($component->get('weekStart'))->toBe($initialWeek);
});

// Monthly Attendance Comparison Tests

test('admin can access monthly attendance comparison', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.attendance.monthly', $this->branch))
        ->assertStatus(200);
});

test('monthly comparison can change months', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(MonthlyAttendanceComparison::class, ['branch' => $this->branch])
        ->call('setMonths', 6);

    expect($component->get('months'))->toBe(6);
});

test('monthly comparison can toggle year-over-year', function () {
    $component = Livewire::actingAs($this->adminUser)
        ->test(MonthlyAttendanceComparison::class, ['branch' => $this->branch])
        ->call('toggleYoY');

    expect($component->get('showYoY'))->toBeTrue();
});

// Service-wise Attendance Tests

test('admin can access service-wise attendance', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.attendance.by-service', $this->branch))
        ->assertStatus(200);
});

// Absent Members Report Tests

test('admin can access absent members report', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.attendance.absent-members', $this->branch))
        ->assertStatus(200);
});

// First-time Visitors Report Tests

test('admin can access first-time visitors report', function () {
    $this->actingAs($this->adminUser)
        ->get(route('reports.attendance.visitors', $this->branch))
        ->assertStatus(200);
});

test('visitors report shows correct stats', function () {
    Visitor::factory()->count(5)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now(),
    ]);

    Visitor::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'visit_date' => now(),
        'is_converted' => true,
    ]);

    $component = Livewire::actingAs($this->adminUser)
        ->test(FirstTimeVisitorsReport::class, ['branch' => $this->branch]);

    $stats = $component->get('summaryStats');
    expect($stats['total'])->toBe(7);
    expect($stats['converted'])->toBe(2);
});
