<?php

declare(strict_types=1);

use App\Enums\ChatbotChannel;
use App\Enums\ChatbotIntent;
use App\Models\Tenant\Branch;
use App\Models\Tenant\ChatbotConversation;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Event;
use App\Models\Tenant\Member;
use App\Services\AI\AiService;
use App\Services\AI\Chatbot\ClusterInfoHandler;
use App\Services\AI\Chatbot\EventsHandler;
use App\Services\AI\Chatbot\GivingHistoryHandler;
use App\Services\AI\Chatbot\PrayerRequestHandler;
use App\Services\AI\ChatbotService;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

uses(TenantTestCase::class);

beforeEach(function (): void {
    $this->setUpTestTenant();

    $this->branch = Branch::factory()->create();

    config(['ai.features.chatbot.use_ai_classification' => false]); // Use heuristic for tests
    config(['ai.features.chatbot.require_member_phone_match' => true]);

    $this->service = new ChatbotService(
        new AiService,
        new GivingHistoryHandler,
        new EventsHandler,
        new PrayerRequestHandler,
        new ClusterInfoHandler
    );
});

afterEach(function (): void {
    $this->tearDownTestTenant();
});

it('creates conversation for new phone number', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'Hello',
        ChatbotChannel::Sms
    );

    expect($result)->toHaveKey('conversation_id');
    expect(ChatbotConversation::count())->toBe(1);
});

it('returns unidentified response for unknown phone', function (): void {
    $result = $this->service->processMessage(
        $this->branch,
        '+233509999999', // Unknown phone
        'Hello',
        ChatbotChannel::Sms
    );

    expect($result['response'])->toContain("couldn't find a member profile");
    expect($result['intent'])->toBe(ChatbotIntent::Unknown);
});

it('allows unidentified phones when not required', function (): void {
    config(['ai.features.chatbot.require_member_phone_match' => false]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233509999999', // Unknown phone
        'help',
        ChatbotChannel::Sms
    );

    expect($result['response'])->not->toContain("couldn't find");
    expect($result['intent'])->toBe(ChatbotIntent::Help);
});

it('classifies greeting intent', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
        'first_name' => 'Kwame',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'Hello',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::Greeting);
    expect($result['response'])->toContain('Kwame');
});

it('classifies help intent', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'help',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::Help);
    expect($result['response'])->toContain('Giving');
    expect($result['response'])->toContain('Events');
});

it('classifies giving history intent', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    Donation::factory()->for($member)->for($this->branch)->create([
        'donation_date' => now()->subDays(5),
        'amount' => 500,
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'What is my giving history?',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::GivingHistory);
    expect($result['response'])->toContain('500');
});

it('classifies events intent', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    Event::factory()->for($this->branch)->upcoming()->create([
        'name' => 'Youth Conference 2025',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'What events are coming up?',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::UpcomingEvents);
    expect($result['response'])->toContain('Youth Conference');
});

it('classifies prayer request intent', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'I want to submit a prayer request',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::PrayerRequest);
});

it('classifies cluster info intent', function (): void {
    $cluster = Cluster::factory()->for($this->branch)->create([
        'name' => 'Grace Fellowship',
    ]);

    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    // Attach member to cluster
    $member->clusters()->attach($cluster->id, [
        'id' => Str::uuid()->toString(),
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'Tell me about my cluster',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::ClusterInfo);
    expect($result['response'])->toContain('Grace Fellowship');
});

it('returns unknown intent for unrecognized messages', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'xyzabc123', // Gibberish
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::Unknown);
    expect($result['response'])->toContain('help');
});

it('stores conversation messages', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'Hello',
        ChatbotChannel::Sms
    );

    $conversation = ChatbotConversation::find($result['conversation_id']);
    expect($conversation->messages()->count())->toBe(2); // Inbound + outbound
});

it('provides different help format for WhatsApp', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'help',
        ChatbotChannel::WhatsApp
    );

    expect($result['intent'])->toBe(ChatbotIntent::Help);
    // WhatsApp supports emojis and formatting
    expect($result['response'])->toContain('📊');
});

it('finds member with various phone formats', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    // Test with different formats
    $result1 = $this->service->processMessage(
        $this->branch,
        '+233501234567', // Full format
        'hello',
        ChatbotChannel::Sms
    );
    expect($result1['intent'])->toBe(ChatbotIntent::Greeting);

    // Create new conversation for second test
    $result2 = $this->service->processMessage(
        $this->branch,
        '233501234567', // Without plus
        'help',
        ChatbotChannel::Sms
    );
    expect($result2['intent'])->toBe(ChatbotIntent::Help);
});

it('handles member with no giving history gracefully', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'giving history',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::GivingHistory);
    // Response says "don't see any donations" when member has no donations
    expect($result['response'])->toContain("don't see any donations");
});

it('handles no upcoming events gracefully', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'upcoming events',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::UpcomingEvents);
    expect($result['response'])->toContain('No upcoming events');
});

it('handles member not in cluster gracefully', function (): void {
    $member = Member::factory()->for($this->branch, 'primaryBranch')->create([
        'phone' => '+233501234567',
    ]);
    // Member not attached to any cluster

    $result = $this->service->processMessage(
        $this->branch,
        '+233501234567',
        'my cluster',
        ChatbotChannel::Sms
    );

    expect($result['intent'])->toBe(ChatbotIntent::ClusterInfo);
    expect($result['response'])->toContain('not currently assigned');
});

it('reports correct feature enabled status', function (): void {
    config(['ai.features.chatbot.enabled' => true]);
    expect($this->service->isEnabled())->toBeTrue();

    config(['ai.features.chatbot.enabled' => false]);
    expect($this->service->isEnabled())->toBeFalse();
});
