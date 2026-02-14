<?php

declare(strict_types=1);

use App\Enums\SmsEngagementLevel;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\CampaignOptimizationResult;
use App\Services\AI\DTOs\SmsEngagementProfile;
use App\Services\AI\SmsCampaignOptimizationService;

beforeEach(function (): void {
    $aiService = new AiService;
    $this->service = new SmsCampaignOptimizationService($aiService);
});

it('returns inactive engagement for opted-out member', function (): void {
    $member = Mockery::mock(Member::class)->makePartial();
    $member->id = 'member-1';
    $member->sms_opt_out = true;

    $profile = $this->service->calculateEngagementScore($member);

    expect($profile->engagementScore)->toBe(0.0);
    expect($profile->engagementLevel)->toBe(SmsEngagementLevel::Inactive);
    expect($profile->factors)->toHaveKey('opt_out_penalty');
});

it('segments recipients by engagement level', function (): void {
    $highEngagementMember = Mockery::mock(Member::class)->makePartial();
    $highEngagementMember->id = 'member-1';
    $highEngagementMember->sms_opt_out = false;
    $highEngagementMember->sms_engagement_level = SmsEngagementLevel::High->value;
    $highEngagementMember->sms_engagement_score = 85;

    $lowEngagementMember = Mockery::mock(Member::class)->makePartial();
    $lowEngagementMember->id = 'member-2';
    $lowEngagementMember->sms_opt_out = false;
    $lowEngagementMember->sms_engagement_level = SmsEngagementLevel::Low->value;
    $lowEngagementMember->sms_engagement_score = 30;

    $members = collect([$highEngagementMember, $lowEngagementMember]);

    $segments = $this->service->segmentRecipients($members);

    expect($segments)->toHaveKey('high');
    expect($segments)->toHaveKey('low');
    expect(count($segments['high']))->toBe(1);
    expect(count($segments['low']))->toBe(1);
});

it('optimizes campaign with recipient segmentation', function (): void {
    $members = collect();

    // Create members with various engagement levels
    foreach (['high', 'medium', 'low', 'inactive'] as $level) {
        $member = Mockery::mock(Member::class)->makePartial();
        $member->id = 'member-'.$level;
        $member->sms_opt_out = false;
        $member->sms_engagement_level = $level;
        $member->sms_engagement_score = match ($level) {
            'high' => 85,
            'medium' => 60,
            'low' => 30,
            'inactive' => 10,
        };
        $member->sms_optimal_send_hour = 9;
        $members->push($member);
    }

    $result = $this->service->optimizeCampaign($members);

    expect($result->totalRecipients)->toBe(4);
    expect($result->segmentCounts)->toHaveKey('high');
    expect($result->segmentCounts)->toHaveKey('medium');
    expect($result->segmentCounts)->toHaveKey('low');
    expect($result->segmentCounts)->toHaveKey('inactive');
    expect($result->engagedPercentage())->toBe(50.0);
    expect($result->atRiskPercentage())->toBe(50.0);
});

it('recommends reduced frequency for low engagement', function (): void {
    expect(SmsEngagementLevel::Low->shouldReduceFrequency())->toBeTrue();
    expect(SmsEngagementLevel::Inactive->shouldReduceFrequency())->toBeTrue();
    expect(SmsEngagementLevel::High->shouldReduceFrequency())->toBeFalse();
    expect(SmsEngagementLevel::Medium->shouldReduceFrequency())->toBeFalse();
});

it('determines correct engagement level from score', function (): void {
    expect(SmsEngagementLevel::fromScore(85))->toBe(SmsEngagementLevel::High);
    expect(SmsEngagementLevel::fromScore(65))->toBe(SmsEngagementLevel::Medium);
    expect(SmsEngagementLevel::fromScore(35))->toBe(SmsEngagementLevel::Low);
    expect(SmsEngagementLevel::fromScore(10))->toBe(SmsEngagementLevel::Inactive);
});

it('provides correct score ranges for engagement levels', function (): void {
    $highRange = SmsEngagementLevel::High->scoreRange();
    expect($highRange['min'])->toBe(80);
    expect($highRange['max'])->toBe(100);

    $mediumRange = SmsEngagementLevel::Medium->scoreRange();
    expect($mediumRange['min'])->toBe(50);
    expect($mediumRange['max'])->toBe(79);

    $lowRange = SmsEngagementLevel::Low->scoreRange();
    expect($lowRange['min'])->toBe(20);
    expect($lowRange['max'])->toBe(49);

    $inactiveRange = SmsEngagementLevel::Inactive->scoreRange();
    expect($inactiveRange['min'])->toBe(0);
    expect($inactiveRange['max'])->toBe(19);
});

it('recommends appropriate monthly message count per level', function (): void {
    expect(SmsEngagementLevel::High->recommendedMonthlyMessages())->toBe(8);
    expect(SmsEngagementLevel::Medium->recommendedMonthlyMessages())->toBe(4);
    expect(SmsEngagementLevel::Low->recommendedMonthlyMessages())->toBe(2);
    expect(SmsEngagementLevel::Inactive->recommendedMonthlyMessages())->toBe(1);
});

it('returns correct badge colors for engagement levels', function (): void {
    expect(SmsEngagementLevel::High->color())->toBe('green');
    expect(SmsEngagementLevel::Medium->color())->toBe('blue');
    expect(SmsEngagementLevel::Low->color())->toBe('yellow');
    expect(SmsEngagementLevel::Inactive->color())->toBe('zinc');
});

it('returns correct icons for engagement levels', function (): void {
    expect(SmsEngagementLevel::High->icon())->toBe('signal');
    expect(SmsEngagementLevel::Medium->icon())->toBe('minus');
    expect(SmsEngagementLevel::Low->icon())->toBe('arrow-trending-down');
    expect(SmsEngagementLevel::Inactive->icon())->toBe('x-circle');
});

it('returns correct labels for engagement levels', function (): void {
    expect(SmsEngagementLevel::High->label())->toBe('High');
    expect(SmsEngagementLevel::Medium->label())->toBe('Medium');
    expect(SmsEngagementLevel::Low->label())->toBe('Low');
    expect(SmsEngagementLevel::Inactive->label())->toBe('Inactive');
});

it('identifies correct time slots', function (): void {
    // Morning (5-11)
    $profile = new SmsEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );
    expect($profile->optimalTimeSlot())->toBe('morning');

    // Afternoon (12-16)
    $profileAfternoon = new SmsEngagementProfile(
        memberId: 'member-2',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 14,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );
    expect($profileAfternoon->optimalTimeSlot())->toBe('afternoon');

    // Evening (17-20)
    $profileEvening = new SmsEngagementProfile(
        memberId: 'member-3',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 19,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );
    expect($profileEvening->optimalTimeSlot())->toBe('evening');
});

it('formats optimal send time correctly', function (): void {
    $profile = new SmsEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 14,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );

    $timeString = $profile->optimalSendTimeString();
    expect($timeString)->toBe('Any day at 2:00 PM');

    $morningProfile = new SmsEngagementProfile(
        memberId: 'member-2',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: 1, // Monday
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );

    expect($morningProfile->optimalSendTimeString())->toBe('Monday at 9:00 AM');
});

it('returns null time string when no optimal hour set', function (): void {
    $profile = new SmsEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: null,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );

    expect($profile->optimalTimeSlot())->toBe('unknown');
    expect($profile->optimalSendTimeString())->toBeNull();
});

it('correctly identifies engaged members', function (): void {
    $highProfile = new SmsEngagementProfile(
        memberId: 'member-1',
        engagementScore: 85,
        engagementLevel: SmsEngagementLevel::High,
        optimalSendHour: 9,
        optimalSendDay: null,
        responseRate: 0.7,
        factors: [],
        recommendations: [],
    );
    expect($highProfile->isEngaged())->toBeTrue();

    $mediumProfile = new SmsEngagementProfile(
        memberId: 'member-2',
        engagementScore: 60,
        engagementLevel: SmsEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: null,
        responseRate: 0.5,
        factors: [],
        recommendations: [],
    );
    expect($mediumProfile->isEngaged())->toBeTrue();

    $lowProfile = new SmsEngagementProfile(
        memberId: 'member-3',
        engagementScore: 30,
        engagementLevel: SmsEngagementLevel::Low,
        optimalSendHour: 9,
        optimalSendDay: null,
        responseRate: 0.2,
        factors: [],
        recommendations: [],
    );
    expect($lowProfile->isEngaged())->toBeFalse();

    $inactiveProfile = new SmsEngagementProfile(
        memberId: 'member-4',
        engagementScore: 10,
        engagementLevel: SmsEngagementLevel::Inactive,
        optimalSendHour: null,
        optimalSendDay: null,
        responseRate: 0.0,
        factors: [],
        recommendations: [],
    );
    expect($inactiveProfile->isEngaged())->toBeFalse();
});

it('calculates campaign optimization result correctly', function (): void {
    $result = new CampaignOptimizationResult(
        segmentedRecipients: [
            'high' => ['member-1'],
            'medium' => ['member-2', 'member-3'],
            'low' => ['member-4'],
            'inactive' => [],
        ],
        optimalSendTimes: [
            'morning' => ['hour' => 9, 'count' => 2],
            'afternoon' => ['hour' => 14, 'count' => 1],
        ],
        recommendations: [
            'Best time to send is morning',
            'Consider re-engagement for inactive members',
        ],
        predictedEngagementRate: 65.0,
        totalRecipients: 4,
        segmentCounts: [
            'high' => 1,
            'medium' => 2,
            'low' => 1,
            'inactive' => 0,
        ],
    );

    expect($result->totalRecipients)->toBe(4);
    expect($result->engagedPercentage())->toBe(75.0); // (1 + 2) / 4 * 100
    expect($result->atRiskPercentage())->toBe(25.0); // (1 + 0) / 4 * 100
    expect($result->recommendations)->toHaveCount(2);
    expect($result->predictedEngagementLevel())->toBe('good'); // 65% is good
    expect($result->badgeColor())->toBe('blue');
});

it('handles empty segments in campaign result', function (): void {
    $result = new CampaignOptimizationResult(
        segmentedRecipients: [
            'high' => ['member-1', 'member-2'],
        ],
        optimalSendTimes: [],
        recommendations: [],
        predictedEngagementRate: 100.0,
        totalRecipients: 2,
        segmentCounts: [
            'high' => 2,
            'medium' => 0,
            'low' => 0,
            'inactive' => 0,
        ],
    );

    expect($result->engagedPercentage())->toBe(100.0);
    expect($result->atRiskPercentage())->toBe(0.0);
    expect($result->predictedEngagementLevel())->toBe('excellent');
});

it('handles zero recipients in campaign result', function (): void {
    $result = new CampaignOptimizationResult(
        segmentedRecipients: [],
        optimalSendTimes: [],
        recommendations: [],
        predictedEngagementRate: 0.0,
        totalRecipients: 0,
        segmentCounts: [],
    );

    expect($result->engagedPercentage())->toBe(0.0);
    expect($result->atRiskPercentage())->toBe(0.0);
    expect($result->predictedEngagementLevel())->toBe('poor');
});

it('correctly checks significant at-risk audience', function (): void {
    $highRisk = new CampaignOptimizationResult(
        segmentedRecipients: [],
        optimalSendTimes: [],
        recommendations: [],
        predictedEngagementRate: 30.0,
        totalRecipients: 100,
        segmentCounts: [
            'high' => 20,
            'medium' => 30,
            'low' => 30,
            'inactive' => 20,
        ],
    );

    expect($highRisk->hasSignificantAtRiskAudience())->toBeTrue();
    expect($highRisk->atRiskPercentage())->toBe(50.0);

    $lowRisk = new CampaignOptimizationResult(
        segmentedRecipients: [],
        optimalSendTimes: [],
        recommendations: [],
        predictedEngagementRate: 80.0,
        totalRecipients: 100,
        segmentCounts: [
            'high' => 50,
            'medium' => 30,
            'low' => 15,
            'inactive' => 5,
        ],
    );

    expect($lowRisk->hasSignificantAtRiskAudience())->toBeFalse();
    expect($lowRisk->atRiskPercentage())->toBe(20.0);
});
