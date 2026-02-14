<?php

declare(strict_types=1);

use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Services\AI\AiService;
use App\Services\AI\AttendanceAnomalyService;
use App\Services\AI\DTOs\AttendanceAnomaly;
use Carbon\Carbon;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = Service::factory()->for($this->branch)->create();

    $aiService = new AiService;
    $this->anomalyService = new AttendanceAnomalyService($aiService);
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

// =============================================================================
// AttendanceAnomaly DTO Tests
// =============================================================================

describe('AttendanceAnomaly DTO', function (): void {
    it('calculates critical severity for extreme decline', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 95,
            baselineAttendance: 3.0,
            recentAttendance: 0.5,
            percentageChange: -83.0,
            factors: [],
            lastAttendanceDate: now()->subDays(14),
        );

        expect($anomaly->severity())->toBe('critical');
    });

    it('calculates high severity for significant decline', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 75,
            baselineAttendance: 2.0,
            recentAttendance: 0.75,
            percentageChange: -62.5,
            factors: [],
            lastAttendanceDate: now()->subDays(10),
        );

        expect($anomaly->severity())->toBe('high');
    });

    it('calculates medium severity for moderate decline', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 50,
            baselineAttendance: 2.0,
            recentAttendance: 1.25,
            percentageChange: -37.5,
            factors: [],
            lastAttendanceDate: now()->subDays(5),
        );

        expect($anomaly->severity())->toBe('medium');
    });

    it('returns correct color class for each severity', function (): void {
        $critical = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'Test',
            score: 90,
            baselineAttendance: 3.0,
            recentAttendance: 0.0,
            percentageChange: -100, // <= -75 = critical
            factors: [],
        );

        $high = new AttendanceAnomaly(
            memberId: 'member-2',
            memberName: 'Test',
            score: 70,
            baselineAttendance: 2.0,
            recentAttendance: 0.5,
            percentageChange: -60, // <= -50 but > -75 = high
            factors: [],
        );

        $medium = new AttendanceAnomaly(
            memberId: 'member-3',
            memberName: 'Test',
            score: 50,
            baselineAttendance: 2.0,
            recentAttendance: 1.0,
            percentageChange: -30, // <= -25 but > -50 = medium
            factors: [],
        );

        expect($critical->colorClass())->toBe('text-red-700');
        expect($high->colorClass())->toBe('text-red-600');
        expect($medium->colorClass())->toBe('text-amber-600');
    });

    it('returns correct badge variant for each severity', function (): void {
        $critical = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'Test',
            score: 90,
            baselineAttendance: 3.0,
            recentAttendance: 0.0,
            percentageChange: -80, // <= -75 = critical
            factors: [],
        );

        $medium = new AttendanceAnomaly(
            memberId: 'member-2',
            memberName: 'Test',
            score: 50,
            baselineAttendance: 2.0,
            recentAttendance: 1.0,
            percentageChange: -35, // <= -25 but > -50 = medium
            factors: [],
        );

        expect($critical->badgeVariant())->toBe('danger');
        expect($medium->badgeVariant())->toBe('warning');
    });

    it('formats change description correctly', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 75,
            baselineAttendance: 2.0,
            recentAttendance: 0.5,
            percentageChange: -75.5,
            factors: [],
        );

        expect($anomaly->changeDescription())->toBe('76% decline in attendance');
    });

    it('calculates days since last attendance', function (): void {
        $lastAttendance = now()->subDays(14);

        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 75,
            baselineAttendance: 2.0,
            recentAttendance: 0.5,
            percentageChange: -75.0,
            factors: [],
            lastAttendanceDate: $lastAttendance,
        );

        expect($anomaly->daysSinceLastAttendance())->toBe(14);
    });

    it('returns null for days since last attendance when no date', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-1',
            memberName: 'John Doe',
            score: 75,
            baselineAttendance: 2.0,
            recentAttendance: 0.5,
            percentageChange: -75.0,
            factors: [],
            lastAttendanceDate: null,
        );

        expect($anomaly->daysSinceLastAttendance())->toBeNull();
    });

    it('converts to array correctly', function (): void {
        $anomaly = new AttendanceAnomaly(
            memberId: 'member-123',
            memberName: 'John Doe',
            score: 80.5,
            baselineAttendance: 2.5,
            recentAttendance: 0.5,
            percentageChange: -80.0,
            factors: ['test_factor' => 'value'],
            lastAttendanceDate: Carbon::parse('2026-02-01'),
        );

        $array = $anomaly->toArray();

        expect($array)->toHaveKey('member_id', 'member-123');
        expect($array)->toHaveKey('member_name', 'John Doe');
        expect($array)->toHaveKey('score', 80.5);
        expect($array)->toHaveKey('baseline_attendance', 2.5);
        expect($array)->toHaveKey('recent_attendance', 0.5);
        expect($array)->toHaveKey('percentage_change', -80.0);
        expect($array)->toHaveKey('factors');
        expect($array['factors'])->toBe(['test_factor' => 'value']);
        expect($array)->toHaveKey('last_attendance_date', '2026-02-01');
        expect($array)->toHaveKey('severity', 'critical');
    });
});

// =============================================================================
// Anomaly Detection Tests
// =============================================================================

describe('Anomaly Detection', function (): void {
    it('detects anomaly for member with significant attendance drop', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create baseline attendance (8 weeks ago to 4 weeks ago) - regularly attending
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // No recent attendance in the last 4 weeks

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->toBeInstanceOf(AttendanceAnomaly::class);
        expect($anomaly->score)->toBeGreaterThan(0);
        expect($anomaly->percentageChange)->toBeLessThan(-50);
    });

    it('returns null for member with stable attendance', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create consistent attendance over the full 12-week period
        for ($i = 0; $i < 12; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->toBeNull();
    });

    it('returns null for member with low baseline attendance', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create only 2 attendance records in 8-week baseline period (avg < 0.5)
        Attendance::factory()
            ->for($member)
            ->for($this->branch)
            ->for($this->service)
            ->create(['date' => now()->subWeeks(6)]);

        Attendance::factory()
            ->for($member)
            ->for($this->branch)
            ->for($this->service)
            ->create(['date' => now()->subWeeks(8)]);

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->toBeNull();
    });

    it('returns null for decline below threshold', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create baseline attendance (8 weeks ago to 4 weeks ago)
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Create some recent attendance (30% decline, below 50% threshold)
        for ($i = 0; $i < 3; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        $anomaly = $this->anomalyService->detectAnomaly($member);

        // Should not trigger anomaly since decline is below threshold
        expect($anomaly)->toBeNull();
    });

    it('includes attendance decline factor', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create solid baseline attendance
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // No recent attendance

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->not->toBeNull();
        expect($anomaly->factors)->toHaveKey('attendance_decline');
        expect($anomaly->factors['attendance_decline'])->toHaveKey('impact', 'high');
    });

    it('includes complete absence factor when no recent attendance', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create baseline attendance only
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->not->toBeNull();
        expect($anomaly->factors)->toHaveKey('complete_absence');
        expect($anomaly->factors['complete_absence']['impact'])->toBe('critical');
    });

    it('includes giving stopped factor when donations also stopped', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create baseline attendance
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Create past donations (4-8 weeks ago) but none recently
        Donation::factory()
            ->for($member)
            ->for($this->branch)
            ->create(['donation_date' => now()->subWeeks(5)]);

        Donation::factory()
            ->for($member)
            ->for($this->branch)
            ->create(['donation_date' => now()->subWeeks(7)]);

        // No recent donations (within last 4 weeks)

        $anomaly = $this->anomalyService->detectAnomaly($member);

        expect($anomaly)->not->toBeNull();
        expect($anomaly->factors)->toHaveKey('giving_stopped');
    });

    it('calculates higher score for complete absence', function (): void {
        $member1 = Member::factory()->for($this->branch, 'primaryBranch')->create();
        $member2 = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Member 1: Baseline attendance, complete absence recently
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member1)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Member 2: Baseline attendance, partial recent attendance (>50% drop but not zero)
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member2)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }
        // One recent attendance
        Attendance::factory()
            ->for($member2)
            ->for($this->branch)
            ->for($this->service)
            ->create(['date' => now()->subWeeks(1)]);

        $anomaly1 = $this->anomalyService->detectAnomaly($member1);
        $anomaly2 = $this->anomalyService->detectAnomaly($member2);

        expect($anomaly1)->not->toBeNull();
        expect($anomaly2)->not->toBeNull();
        expect($anomaly1->score)->toBeGreaterThan($anomaly2->score);
    });

    it('calculates higher score for previously active members', function (): void {
        // Create a second service to avoid unique constraint issues
        $service2 = Service::factory()->for($this->branch)->create();

        $casualMember = Member::factory()->for($this->branch, 'primaryBranch')->create();
        $activeMember = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Casual member: baseline of ~0.5 per week (4 times in 8 weeks)
        for ($i = 4; $i <= 11; $i += 2) {
            Attendance::factory()
                ->for($casualMember)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Active member: baseline of 2 per week (16 times in 8 weeks)
        for ($i = 4; $i <= 11; $i++) {
            // Two attendance per week on different services
            Attendance::factory()
                ->for($activeMember)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);

            Attendance::factory()
                ->for($activeMember)
                ->for($this->branch)
                ->for($service2)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Both have no recent attendance

        $casualAnomaly = $this->anomalyService->detectAnomaly($casualMember);
        $activeAnomaly = $this->anomalyService->detectAnomaly($activeMember);

        expect($casualAnomaly)->not->toBeNull();
        expect($activeAnomaly)->not->toBeNull();
        // Active member should have higher score (more concerning drop due to higher baseline)
        expect($activeAnomaly->score)->toBeGreaterThan($casualAnomaly->score);
    });
});

// =============================================================================
// Branch-level Operations Tests
// =============================================================================

describe('Branch-level Operations', function (): void {
    it('detects anomalies for all members in branch', function (): void {
        // Create 3 members with attendance drops
        for ($m = 0; $m < 3; $m++) {
            $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

            // Baseline attendance
            for ($i = 4; $i <= 11; $i++) {
                Attendance::factory()
                    ->for($member)
                    ->for($this->branch)
                    ->for($this->service)
                    ->create(['date' => now()->subWeeks($i)->addDays($m)]); // Different days to avoid conflicts
            }
        }

        $anomalies = $this->anomalyService->detectAnomaliesForBranch($this->branch->id);

        expect($anomalies)->toHaveCount(3);
        expect($anomalies->first())->toBeInstanceOf(AttendanceAnomaly::class);
    });

    it('limits results to specified count', function (): void {
        // Create 5 members with attendance drops
        for ($m = 0; $m < 5; $m++) {
            $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

            for ($i = 4; $i <= 11; $i++) {
                Attendance::factory()
                    ->for($member)
                    ->for($this->branch)
                    ->for($this->service)
                    ->create(['date' => now()->subWeeks($i)->addDays($m)]);
            }
        }

        $anomalies = $this->anomalyService->detectAnomaliesForBranch($this->branch->id, limit: 3);

        expect($anomalies)->toHaveCount(3);
    });

    it('sorts anomalies by score descending', function (): void {
        // Create a second service to avoid unique constraint issues
        $service2 = Service::factory()->for($this->branch)->create();

        // Create members with varying severity of drops
        $activeMember = Member::factory()->for($this->branch, 'primaryBranch')->create();
        $casualMember = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Active member: high baseline (2 per week using different services)
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($activeMember)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);

            Attendance::factory()
                ->for($activeMember)
                ->for($this->branch)
                ->for($service2)
                ->create(['date' => now()->subWeeks($i)]);
        }

        // Casual member: lower baseline (every other week)
        for ($i = 4; $i <= 11; $i += 2) {
            Attendance::factory()
                ->for($casualMember)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)->addDays(1)]);
        }

        $anomalies = $this->anomalyService->detectAnomaliesForBranch($this->branch->id);

        // At least one anomaly should be detected
        expect($anomalies->count())->toBeGreaterThanOrEqual(1);
        // If there are multiple, they should be sorted by score
        if ($anomalies->count() > 1) {
            expect($anomalies->first()->score)->toBeGreaterThanOrEqual($anomalies->last()->score);
        }
    });

    it('updates member anomaly scores in database', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create();

        // Create baseline attendance
        for ($i = 4; $i <= 11; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        $updatedCount = $this->anomalyService->updateMemberAnomalyScores($this->branch->id);

        expect($updatedCount)->toBe(1);

        $member->refresh();

        expect($member->attendance_anomaly_score)->not->toBeNull();
        expect($member->attendance_anomaly_score)->toBeGreaterThan(0);
        expect($member->attendance_anomaly_detected_at)->not->toBeNull();
    });

    it('clears anomaly scores for members no longer anomalous', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => 75,
            'attendance_anomaly_detected_at' => now()->subDays(3),
        ]);

        // Create consistent attendance (no anomaly)
        for ($i = 0; $i < 12; $i++) {
            Attendance::factory()
                ->for($member)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subWeeks($i)]);
        }

        $this->anomalyService->updateMemberAnomalyScores($this->branch->id);

        $member->refresh();

        expect($member->attendance_anomaly_score)->toBeNull();
        expect($member->attendance_anomaly_detected_at)->toBeNull();
    });

    it('returns members with active anomalies', function (): void {
        $memberWithAnomaly = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => 80,
            'attendance_anomaly_detected_at' => now()->subDays(2),
        ]);

        $memberWithOldAnomaly = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => 70,
            'attendance_anomaly_detected_at' => now()->subDays(10), // Older than 7 days
        ]);

        $memberWithoutAnomaly = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => null,
            'attendance_anomaly_detected_at' => null,
        ]);

        $members = $this->anomalyService->getMembersWithAnomalies($this->branch->id);

        expect($members)->toHaveCount(1);
        expect($members->first()->id)->toBe($memberWithAnomaly->id);
    });

    it('only returns anomalies detected within 7 days', function (): void {
        $recentAnomaly = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => 80,
            'attendance_anomaly_detected_at' => now()->subDays(5),
        ]);

        $oldAnomaly = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'attendance_anomaly_score' => 85,
            'attendance_anomaly_detected_at' => now()->subDays(8),
        ]);

        $members = $this->anomalyService->getMembersWithAnomalies($this->branch->id);

        expect($members)->toHaveCount(1);
        expect($members->pluck('id'))->toContain($recentAnomaly->id);
        expect($members->pluck('id'))->not->toContain($oldAnomaly->id);
    });
});
