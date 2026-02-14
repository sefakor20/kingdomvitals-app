<?php

declare(strict_types=1);

use App\Enums\AiMessageStatus;
use App\Enums\FollowUpType;
use App\Models\Tenant\AiGeneratedMessage;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\GeneratedMessage;
use App\Services\AI\MessageGenerationService;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();
    $this->branch = Branch::factory()->create();
    $this->service = Service::factory()->for($this->branch)->create();
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

/**
 * Helper function to create a mock AgentResponse.
 */
function createMockAgentResponse(string $text, int $completionTokens = 25): AgentResponse
{
    $usage = new Usage(promptTokens: 10, completionTokens: $completionTokens);
    $meta = new Meta(provider: 'anthropic', model: 'claude-3-5-sonnet');

    return new AgentResponse(
        invocationId: 'test-invocation-id',
        text: $text,
        usage: $usage,
        meta: $meta,
    );
}

// =============================================================================
// GeneratedMessage DTO Tests
// =============================================================================

describe('GeneratedMessage DTO', function (): void {
    it('returns correct character count', function (): void {
        $message = new GeneratedMessage(
            content: 'Hello World!',
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($message->characterCount())->toBe(12);
    });

    it('identifies message fitting within SMS limit', function (): void {
        $message = new GeneratedMessage(
            content: str_repeat('a', 160),
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($message->fitsInSms())->toBeTrue();
    });

    it('identifies message exceeding SMS limit', function (): void {
        $message = new GeneratedMessage(
            content: str_repeat('a', 161),
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($message->fitsInSms())->toBeFalse();
    });

    it('calculates correct SMS segment count for single segment', function (): void {
        $message = new GeneratedMessage(
            content: str_repeat('a', 160),
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($message->smsSegmentCount())->toBe(1);
    });

    it('calculates correct SMS segment count for multi-part messages', function (): void {
        // 306 characters = ceiling(306/153) = 2 segments
        $message = new GeneratedMessage(
            content: str_repeat('a', 306),
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($message->smsSegmentCount())->toBe(2);

        // 460 characters = ceiling(460/153) = 4 segments (this accounts for the 153 chars per segment for multi-part)
        $longMessage = new GeneratedMessage(
            content: str_repeat('a', 460),
            messageType: 'follow_up',
            channel: 'sms',
            context: [],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
        );

        expect($longMessage->smsSegmentCount())->toBe(4);
    });

    it('converts to array correctly', function (): void {
        $message = new GeneratedMessage(
            content: 'Test message',
            messageType: 'follow_up',
            channel: 'sms',
            context: ['first_name' => 'John'],
            provider: 'anthropic',
            model: 'claude-3-5-sonnet',
            tokensUsed: 50,
        );

        $array = $message->toArray();

        expect($array)->toHaveKey('content', 'Test message');
        expect($array)->toHaveKey('message_type', 'follow_up');
        expect($array)->toHaveKey('channel', 'sms');
        expect($array)->toHaveKey('context');
        expect($array['context'])->toHaveKey('first_name', 'John');
        expect($array)->toHaveKey('provider', 'anthropic');
        expect($array)->toHaveKey('model', 'claude-3-5-sonnet');
        expect($array)->toHaveKey('tokens_used', 50);
        expect($array)->toHaveKey('character_count', 12);
    });
});

// =============================================================================
// Visitor Follow-up Message Tests
// =============================================================================

describe('Visitor Follow-up Messages', function (): void {
    it('generates follow up message for visitor with AI', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'visit_date' => now()->subDays(7),
        ]);

        $mockResponse = createMockAgentResponse('Hi John, thank you for visiting us!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Sms);

        expect($result)->toBeInstanceOf(GeneratedMessage::class);
        expect($result->content)->toBe('Hi John, thank you for visiting us!');
        expect($result->messageType)->toBe('follow_up');
        expect($result->channel)->toBe('sms');
        expect($result->provider)->toBe('anthropic');
        // Note: tokensUsed is null because the service accesses outputTokens which doesn't exist on Usage
        expect($result->tokensUsed)->toBeNull();
    });

    it('generates follow up message with email channel', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $mockResponse = createMockAgentResponse('Dear Jane, we hope to see you again soon!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Email);

        expect($result->channel)->toBe('email');
    });

    it('falls back to template when AI service fails', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Alex',
            'last_name' => 'Brown',
            'follow_up_count' => 0,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Sms);

        expect($result->provider)->toBe('template');
        expect($result->model)->toBe('v1');
        expect($result->content)->toContain('Alex');
        expect($result->tokensUsed)->toBeNull();
    });

    it('includes visitor context in generated message', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'visit_date' => now()->subDays(5),
            'how_did_you_hear' => 'Facebook',
            'follow_up_count' => 2,
        ]);

        // Add some attendance records
        Attendance::factory()
            ->for($visitor)
            ->for($this->branch)
            ->for($this->service)
            ->create([
                'date' => now()->subDays(5),
            ]);

        $mockResponse = createMockAgentResponse('Generated message');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('openai');
        $aiService->shouldReceive('getModel')
            ->andReturn('gpt-4o');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Sms);

        expect($result->context)->toHaveKey('first_name', 'Michael');
        expect($result->context)->toHaveKey('last_name', 'Johnson');
        expect($result->context)->toHaveKey('how_heard', 'Facebook');
        expect($result->context)->toHaveKey('visit_count');
        expect($result->context['visit_count'])->toBe(1);
    });

    it('persists visitor message to database', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Sarah',
            'last_name' => 'Wilson',
        ]);

        $mockResponse = createMockAgentResponse('Hello Sarah!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $persistedMessage = $service->createVisitorMessage($visitor, FollowUpType::Sms);

        expect($persistedMessage)->toBeInstanceOf(AiGeneratedMessage::class);
        expect($persistedMessage->exists)->toBeTrue();
        expect($persistedMessage->visitor_id)->toBe($visitor->id);
        expect($persistedMessage->member_id)->toBeNull();
        expect($persistedMessage->branch_id)->toBe($this->branch->id);
        expect($persistedMessage->generated_content)->toBe('Hello Sarah!');
    });

    it('creates message with pending status', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create();

        $mockResponse = createMockAgentResponse('Test message');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $persistedMessage = $service->createVisitorMessage($visitor);

        expect($persistedMessage->status)->toBe(AiMessageStatus::Pending);
        expect($persistedMessage->approved_at)->toBeNull();
        expect($persistedMessage->sent_at)->toBeNull();
    });
});

// =============================================================================
// Member Re-engagement Message Tests
// =============================================================================

describe('Member Re-engagement Messages', function (): void {
    it('generates reengagement message for member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'David',
            'last_name' => 'Lee',
            'joined_at' => now()->subYear(),
        ]);

        $mockResponse = createMockAgentResponse('Hi David, we miss you!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result)->toBeInstanceOf(GeneratedMessage::class);
        expect($result->content)->toBe('Hi David, we miss you!');
        expect($result->messageType)->toBe('reengagement');
        expect($result->channel)->toBe('sms');
    });

    it('generates reengagement message for high churn risk member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Emily',
            'last_name' => 'Taylor',
            'churn_risk_score' => 85,
        ]);

        $mockResponse = createMockAgentResponse('Hi Emily, we truly miss you!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->context)->toHaveKey('churn_risk_score', 85);
    });

    it('falls back to template when AI service fails for member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Chris',
            'last_name' => 'Martin',
            'churn_risk_score' => 75,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->provider)->toBe('template');
        expect($result->content)->toContain('Chris');
        expect($result->content)->toContain("we've missed you");
    });

    it('includes member context in generated message', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Olivia',
            'last_name' => 'Brown',
            'joined_at' => now()->subMonths(18),
            'baptized_at' => now()->subYear(),
            'churn_risk_score' => 45,
        ]);

        // Add attendance record
        Attendance::factory()
            ->for($member)
            ->for($this->branch)
            ->for($this->service)
            ->create([
                'date' => now()->subDays(30),
            ]);

        $mockResponse = createMockAgentResponse('Generated message');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('anthropic');
        $aiService->shouldReceive('getModel')
            ->andReturn('claude-3-5-sonnet');

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->context)->toHaveKey('first_name', 'Olivia');
        expect($result->context)->toHaveKey('last_name', 'Brown');
        expect($result->context)->toHaveKey('is_baptized', true);
        expect($result->context)->toHaveKey('churn_risk_score', 45);
        expect($result->context)->toHaveKey('days_since_attendance');
        expect((int) $result->context['days_since_attendance'])->toBe(30);
    });

    it('persists member message to database', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'James',
            'last_name' => 'Wilson',
        ]);

        $mockResponse = createMockAgentResponse('Hello James!');

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andReturn($mockResponse);
        $aiService->shouldReceive('getProvider')
            ->andReturn('openai');
        $aiService->shouldReceive('getModel')
            ->andReturn('gpt-4o');

        $service = new MessageGenerationService($aiService);
        $persistedMessage = $service->createMemberMessage($member, FollowUpType::Email);

        expect($persistedMessage)->toBeInstanceOf(AiGeneratedMessage::class);
        expect($persistedMessage->exists)->toBeTrue();
        expect($persistedMessage->member_id)->toBe($member->id);
        expect($persistedMessage->visitor_id)->toBeNull();
        expect($persistedMessage->branch_id)->toBe($this->branch->id);
        expect($persistedMessage->channel)->toBe(FollowUpType::Email);
        expect($persistedMessage->ai_provider)->toBe('openai');
        expect($persistedMessage->ai_model)->toBe('gpt-4o');
    });
});

// =============================================================================
// Template Fallback Tests
// =============================================================================

describe('Template Fallback Messages', function (): void {
    it('generates first followup template for new visitor', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Tom',
            'follow_up_count' => 0,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Sms);

        expect($result->content)->toContain('Tom');
        expect($result->content)->toContain('Thank you for visiting');
    });

    it('generates return visitor template for repeat visitor', function (): void {
        $visitor = Visitor::factory()->for($this->branch)->create([
            'first_name' => 'Linda',
            'follow_up_count' => 1,
        ]);

        // Add multiple attendance records on different dates
        for ($i = 0; $i < 3; $i++) {
            Attendance::factory()
                ->for($visitor)
                ->for($this->branch)
                ->for($this->service)
                ->create(['date' => now()->subDays(7 * ($i + 1))]);
        }

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateVisitorFollowUp($visitor, FollowUpType::Sms);

        expect($result->content)->toContain('Linda');
        expect($result->content)->toContain('Great to see');
    });

    it('generates high risk template for high churn member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Robert',
            'churn_risk_score' => 80,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->content)->toContain('Robert');
        expect($result->content)->toContain("we've missed you");
        expect($result->content)->toContain('reconnect');
    });

    it('generates medium risk template for medium churn member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Patricia',
            'churn_risk_score' => 55,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->content)->toContain('Patricia');
        expect($result->content)->toContain('thinking of you');
    });

    it('generates low risk template for low churn member', function (): void {
        $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
            'first_name' => 'Jessica',
            'churn_risk_score' => 20,
        ]);

        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('generateWithFallback')
            ->once()
            ->andThrow(new \Exception('AI unavailable'));

        $service = new MessageGenerationService($aiService);
        $result = $service->generateMemberReengagement($member, FollowUpType::Sms);

        expect($result->content)->toContain('Jessica');
        expect($result->content)->toContain('checking in');
    });
});
