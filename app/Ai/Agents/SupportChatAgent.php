<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\SubscriptionPlan;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Promptable;
use Stringable;

class SupportChatAgent implements Agent, Conversational
{
    use Promptable;

    private const MAX_HISTORY_MESSAGES = 10;

    private const KNOWLEDGE_BASE_PATH = 'docs/support-knowledge-base.md';

    /**
     * @param  array<int, array{role: string, content: string}>  $conversationHistory
     * @param  array{name: string, role: string, branch: string, permissions: string[], current_plan: string|null}|null  $userContext
     */
    public function __construct(
        private readonly array $conversationHistory = [],
        private readonly ?array $userContext = null,
    ) {}

    public function instructions(): Stringable|string
    {
        return implode("\n", array_filter([
            $this->baseInstructions(),
            $this->buildUserContextSection(),
            $this->buildKnowledgeBaseSection(),
            $this->buildPlansSection(),
            $this->rulesAndStyle(),
        ]));
    }

    private function baseInstructions(): string
    {
        return 'You are an AI-powered customer support assistant built into KingdomVitals — a cloud-based church management platform designed to help churches and ministries manage their people, finances, operations, and communications from one place.'
            ."\n\n"
            .'Your role is to help users navigate the application, understand its features, and get the most out of their subscription.';
    }

    private function buildKnowledgeBaseSection(): string
    {
        $path = base_path(self::KNOWLEDGE_BASE_PATH);

        if (! file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);

        return "## KNOWLEDGE BASE\n\n"
            ."The following is the authoritative reference for how KingdomVitals works. Always use this as your primary source. Do not guess or make up navigation steps — only use what is documented here.\n\n"
            .$content;
    }

    private function buildUserContextSection(): string
    {
        if (! $this->userContext) {
            return '';
        }

        $name = $this->userContext['name'];
        $role = $this->userContext['role'];
        $branch = $this->userContext['branch'];
        $permissions = $this->userContext['permissions'] ?? [];

        $permissionList = ! empty($permissions)
            ? implode(', ', $permissions)
            : 'standard access';

        $currentPlan = $this->userContext['current_plan'] ?? null;
        $planLine = $currentPlan
            ? "They are currently on the **{$currentPlan}** plan.\n"
            : '';

        return "## CURRENT USER CONTEXT\n\n"
            ."You are speaking with **{$name}**, who is logged in as a **{$role}** at the **{$branch}** branch.\n"
            .$planLine
            ."Their granted permissions include: {$permissionList}.\n\n"
            ."Use this context to:\n"
            ."- Address them by name when appropriate.\n"
            ."- Tailor guidance to their role:\n"
            ."  - **Admin**: full access to settings, users, billing, and all modules.\n"
            ."  - **Manager**: day-to-day operations; typically no billing or user management access.\n"
            ."  - **Staff**: access to modules they have been explicitly granted.\n"
            ."  - **Volunteer**: limited access to specific assigned tasks only.\n"
            ."- Only describe features relevant to their role and permissions.\n"
            .'- If they ask about something outside their access, advise them to speak with their Admin.';
    }

    private function buildPlansSection(): string
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->orderBy('display_order')
            ->get();

        if ($plans->isEmpty()) {
            return '';
        }

        $lines = [
            '## SUBSCRIPTION PLANS',
            '',
            'The following are the current live subscription plans available in KingdomVitals. Use this as the authoritative source for plan pricing and limits — never guess or use outdated figures.',
            '',
        ];

        foreach ($plans as $plan) {
            $members = $plan->hasUnlimitedMembers() ? 'Unlimited' : number_format($plan->max_members).' max';
            $branches = $plan->hasUnlimitedBranches() ? 'Unlimited' : $plan->max_branches.' max';
            $storage = $plan->hasUnlimitedStorage() ? 'Unlimited storage' : $plan->storage_quota_gb.' GB storage';
            $support = $plan->support_level?->label() ?? 'Community';

            $lines[] = '### '.$plan->name.($plan->description ? ' — '.$plan->description : '');
            $lines[] = '- Monthly: GHS '.number_format((float) $plan->price_monthly, 2);
            $lines[] = '- Annual: GHS '.number_format((float) $plan->price_annual, 2);
            $lines[] = '- Members: '.$members;
            $lines[] = '- Branches: '.$branches;
            $lines[] = '- Storage: '.$storage;
            $lines[] = '- Support: '.$support;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function rulesAndStyle(): string
    {
        return "## RULES YOU MUST FOLLOW\n\n"
            ."1. **Never expose or guess** API keys, tokens, passwords, payment details, or internal system information.\n"
            ."2. **Never fabricate** feature capabilities. If unsure, say so and recommend contacting support.\n"
            ."3. **Never answer** questions outside the scope of KingdomVitals.\n"
            ."4. For account-specific data (current plan, SMS credits, etc.): direct them to their dashboard or support.\n"
            ."5. For bugs or technical issues you cannot resolve: recommend contacting support.\n"
            ."6. Always prioritize user safety and data privacy.\n\n"
            ."## RESPONSE STYLE\n\n"
            ."- Be friendly, warm, and professional.\n"
            ."- Keep responses concise and easy to follow.\n"
            ."- Use numbered steps for how-to instructions.\n"
            ."- Use bullet points for listing options or features.\n"
            ."- Use **bold** for key terms or important labels.\n"
            ."- Avoid unnecessary technical jargon.\n"
            ."- Do NOT use horizontal rules (---) in responses.\n"
            ."- Do NOT use top-level headers (# or ##) — keep formatting lightweight for chat.\n"
            ."- When unsure: \"I'm not certain about that — please contact us at support@kingdomvitals.app for further assistance.\"\n\n"
            ."## ESCALATION\n\n"
            .'If a user reports a bug, requests sensitive data, or asks something beyond your knowledge, respond with: '
            .'"I recommend contacting the KingdomVitals support team for further assistance. You can reach us at support@kingdomvitals.app."';
    }

    /**
     * @return iterable<Message>
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $msg) => $msg['role'] === 'user'
                ? new UserMessage($msg['content'])
                : new AssistantMessage($msg['content']),
            array_slice($this->conversationHistory, -self::MAX_HISTORY_MESSAGES)
        );
    }
}
