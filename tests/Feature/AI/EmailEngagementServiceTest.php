<?php

declare(strict_types=1);

use App\Enums\EmailEngagementLevel;
use App\Models\Tenant\Member;
use App\Services\AI\DTOs\EmailEngagementProfile;
use App\Services\AI\EmailEngagementService;

beforeEach(function (): void {
    $this->service = new EmailEngagementService;
});

it('service can be instantiated', function (): void {
    expect($this->service)->toBeInstanceOf(EmailEngagementService::class);
});

it('segments recipients by engagement level', function (): void {
    $highEngagementMember = Mockery::mock(Member::class)->makePartial();
    $highEngagementMember->id = 'member-1';
    $highEngagementMember->email = 'high@example.com';
    $highEngagementMember->email_engagement_level = EmailEngagementLevel::High->value;
    $highEngagementMember->email_engagement_score = 85;

    $lowEngagementMember = Mockery::mock(Member::class)->makePartial();
    $lowEngagementMember->id = 'member-2';
    $lowEngagementMember->email = 'low@example.com';
    $lowEngagementMember->email_engagement_level = EmailEngagementLevel::Low->value;
    $lowEngagementMember->email_engagement_score = 30;

    $members = collect([$highEngagementMember, $lowEngagementMember]);

    $segments = $this->service->segmentRecipients($members);

    expect($segments)->toHaveKey('high');
    expect($segments)->toHaveKey('low');
    expect(count($segments['high']))->toBe(1);
    expect(count($segments['low']))->toBe(1);
});

it('recommends reduced frequency for low engagement', function (): void {
    expect(EmailEngagementLevel::Low->shouldReduceFrequency())->toBeTrue();
    expect(EmailEngagementLevel::Inactive->shouldReduceFrequency())->toBeTrue();
    expect(EmailEngagementLevel::High->shouldReduceFrequency())->toBeFalse();
    expect(EmailEngagementLevel::Medium->shouldReduceFrequency())->toBeFalse();
});

it('determines correct engagement level from score', function (): void {
    expect(EmailEngagementLevel::fromScore(85))->toBe(EmailEngagementLevel::High);
    expect(EmailEngagementLevel::fromScore(65))->toBe(EmailEngagementLevel::Medium);
    expect(EmailEngagementLevel::fromScore(35))->toBe(EmailEngagementLevel::Low);
    expect(EmailEngagementLevel::fromScore(10))->toBe(EmailEngagementLevel::Inactive);
});

it('provides correct score ranges for engagement levels', function (): void {
    $highRange = EmailEngagementLevel::High->scoreRange();
    expect($highRange['min'])->toBe(80);
    expect($highRange['max'])->toBe(100);

    $mediumRange = EmailEngagementLevel::Medium->scoreRange();
    expect($mediumRange['min'])->toBe(50);
    expect($mediumRange['max'])->toBe(79);

    $lowRange = EmailEngagementLevel::Low->scoreRange();
    expect($lowRange['min'])->toBe(20);
    expect($lowRange['max'])->toBe(49);

    $inactiveRange = EmailEngagementLevel::Inactive->scoreRange();
    expect($inactiveRange['min'])->toBe(0);
    expect($inactiveRange['max'])->toBe(19);
});

it('recommends appropriate monthly email count per level', function (): void {
    expect(EmailEngagementLevel::High->recommendedMonthlyEmails())->toBe(8);
    expect(EmailEngagementLevel::Medium->recommendedMonthlyEmails())->toBe(4);
    expect(EmailEngagementLevel::Low->recommendedMonthlyEmails())->toBe(2);
    expect(EmailEngagementLevel::Inactive->recommendedMonthlyEmails())->toBe(1);
});

it('returns correct badge colors for engagement levels', function (): void {
    expect(EmailEngagementLevel::High->color())->toBe('green');
    expect(EmailEngagementLevel::Medium->color())->toBe('blue');
    expect(EmailEngagementLevel::Low->color())->toBe('yellow');
    expect(EmailEngagementLevel::Inactive->color())->toBe('zinc');
});

it('returns correct icons for engagement levels', function (): void {
    expect(EmailEngagementLevel::High->icon())->toBe('envelope-open');
    expect(EmailEngagementLevel::Medium->icon())->toBe('envelope');
    expect(EmailEngagementLevel::Low->icon())->toBe('arrow-trending-down');
    expect(EmailEngagementLevel::Inactive->icon())->toBe('x-circle');
});

it('returns correct labels for engagement levels', function (): void {
    expect(EmailEngagementLevel::High->label())->toBe('High');
    expect(EmailEngagementLevel::Medium->label())->toBe('Medium');
    expect(EmailEngagementLevel::Low->label())->toBe('Low');
    expect(EmailEngagementLevel::Inactive->label())->toBe('Inactive');
});

it('identifies correct time slots', function (): void {
    // Morning (5-11)
    $profile = new EmailEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: null,
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );
    expect($profile->optimalTimeSlot())->toBe('morning');

    // Afternoon (12-16)
    $profileAfternoon = new EmailEngagementProfile(
        memberId: 'member-2',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 14,
        optimalSendDay: null,
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );
    expect($profileAfternoon->optimalTimeSlot())->toBe('afternoon');

    // Evening (17-20)
    $profileEvening = new EmailEngagementProfile(
        memberId: 'member-3',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 19,
        optimalSendDay: null,
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );
    expect($profileEvening->optimalTimeSlot())->toBe('evening');
});

it('formats optimal send time correctly', function (): void {
    $profile = new EmailEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 14,
        optimalSendDay: null,
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );

    $timeString = $profile->optimalSendTimeString();
    expect($timeString)->toBe('Any day at 2:00 PM');

    $morningProfile = new EmailEngagementProfile(
        memberId: 'member-2',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: 1, // Monday
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );

    expect($morningProfile->optimalSendTimeString())->toBe('Monday at 9:00 AM');
});

it('returns null time string when no optimal hour set', function (): void {
    $profile = new EmailEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: null,
        optimalSendDay: null,
        openRate: 45.0,
        clickRate: 10.0,
        factors: [],
        recommendations: [],
    );

    expect($profile->optimalTimeSlot())->toBe('unknown');
    expect($profile->optimalSendTimeString())->toBeNull();
});

it('correctly identifies engaged members', function (): void {
    $highProfile = new EmailEngagementProfile(
        memberId: 'member-1',
        engagementScore: 85,
        engagementLevel: EmailEngagementLevel::High,
        optimalSendHour: 9,
        optimalSendDay: null,
        openRate: 70.0,
        clickRate: 25.0,
        factors: [],
        recommendations: [],
    );
    expect($highProfile->isEngaged())->toBeTrue();

    $mediumProfile = new EmailEngagementProfile(
        memberId: 'member-2',
        engagementScore: 60,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 9,
        optimalSendDay: null,
        openRate: 50.0,
        clickRate: 15.0,
        factors: [],
        recommendations: [],
    );
    expect($mediumProfile->isEngaged())->toBeTrue();

    $lowProfile = new EmailEngagementProfile(
        memberId: 'member-3',
        engagementScore: 30,
        engagementLevel: EmailEngagementLevel::Low,
        optimalSendHour: 9,
        optimalSendDay: null,
        openRate: 20.0,
        clickRate: 5.0,
        factors: [],
        recommendations: [],
    );
    expect($lowProfile->isEngaged())->toBeFalse();

    $inactiveProfile = new EmailEngagementProfile(
        memberId: 'member-4',
        engagementScore: 10,
        engagementLevel: EmailEngagementLevel::Inactive,
        optimalSendHour: null,
        optimalSendDay: null,
        openRate: 0.0,
        clickRate: 0.0,
        factors: [],
        recommendations: [],
    );
    expect($inactiveProfile->isEngaged())->toBeFalse();
});

it('converts profile to array correctly', function (): void {
    $profile = new EmailEngagementProfile(
        memberId: 'member-1',
        engagementScore: 75.5,
        engagementLevel: EmailEngagementLevel::Medium,
        optimalSendHour: 10,
        optimalSendDay: 3,
        openRate: 55.2,
        clickRate: 12.5,
        factors: ['open_rate' => 15.0, 'click_rate' => 10.0],
        recommendations: ['Consider A/B testing subject lines'],
    );

    $array = $profile->toArray();

    expect($array['member_id'])->toBe('member-1');
    expect($array['engagement_score'])->toBe(75.5);
    expect($array['engagement_level'])->toBe('medium');
    expect($array['optimal_send_hour'])->toBe(10);
    expect($array['optimal_send_day'])->toBe(3);
    expect($array['open_rate'])->toBe(55.2);
    expect($array['click_rate'])->toBe(12.5);
    expect($array['factors'])->toBe(['open_rate' => 15.0, 'click_rate' => 10.0]);
    expect($array['recommendations'])->toBe(['Consider A/B testing subject lines']);
    expect($array)->toHaveKey('calculated_at');
});

it('creates profile from array correctly', function (): void {
    $data = [
        'member_id' => 'member-1',
        'engagement_score' => 75.5,
        'engagement_level' => 'medium',
        'optimal_send_hour' => 10,
        'optimal_send_day' => 3,
        'open_rate' => 55.2,
        'click_rate' => 12.5,
        'factors' => ['open_rate' => 15.0],
        'recommendations' => ['Test recommendation'],
        'provider' => 'heuristic',
        'model' => 'v1',
    ];

    $profile = EmailEngagementProfile::fromArray($data);

    expect($profile->memberId)->toBe('member-1');
    expect($profile->engagementScore)->toBe(75.5);
    expect($profile->engagementLevel)->toBe(EmailEngagementLevel::Medium);
    expect($profile->optimalSendHour)->toBe(10);
    expect($profile->optimalSendDay)->toBe(3);
    expect($profile->openRate)->toBe(55.2);
    expect($profile->clickRate)->toBe(12.5);
    expect($profile->factors)->toBe(['open_rate' => 15.0]);
    expect($profile->recommendations)->toBe(['Test recommendation']);
});
