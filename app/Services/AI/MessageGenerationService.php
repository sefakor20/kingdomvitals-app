<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AiMessageStatus;
use App\Enums\FollowUpType;
use App\Models\Tenant\AiGeneratedMessage;
use App\Models\Tenant\Member;
use App\Models\Tenant\Visitor;
use App\Services\AI\DTOs\GeneratedMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageGenerationService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Generate a follow-up message for a visitor.
     */
    public function generateVisitorFollowUp(
        Visitor $visitor,
        FollowUpType $channel = FollowUpType::Sms
    ): GeneratedMessage {
        $context = $this->buildVisitorContext($visitor);
        $prompt = $this->buildVisitorPrompt($visitor, $context, $channel);
        $systemPrompt = config('ai.prompts.follow_up_system');

        try {
            $response = $this->aiService->generateWithFallback($prompt, $systemPrompt);

            return new GeneratedMessage(
                content: trim($response->text),
                messageType: 'follow_up',
                channel: $channel->value,
                context: $context,
                provider: $this->aiService->getProvider(),
                model: $this->aiService->getModel(),
                tokensUsed: $response->usage?->outputTokens ?? null,
            );
        } catch (Throwable $e) {
            Log::warning('AI message generation failed, using template', [
                'error' => $e->getMessage(),
                'visitor_id' => $visitor->id,
            ]);

            return $this->generateTemplateFollowUp($visitor, $channel, $context);
        }
    }

    /**
     * Generate a re-engagement message for a member.
     */
    public function generateMemberReengagement(
        Member $member,
        FollowUpType $channel = FollowUpType::Sms
    ): GeneratedMessage {
        $context = $this->buildMemberContext($member);
        $prompt = $this->buildMemberPrompt($member, $context, $channel);
        $systemPrompt = config('ai.prompts.reengagement_system');

        try {
            $response = $this->aiService->generateWithFallback($prompt, $systemPrompt);

            return new GeneratedMessage(
                content: trim($response->text),
                messageType: 'reengagement',
                channel: $channel->value,
                context: $context,
                provider: $this->aiService->getProvider(),
                model: $this->aiService->getModel(),
                tokensUsed: $response->usage?->outputTokens ?? null,
            );
        } catch (Throwable $e) {
            Log::warning('AI message generation failed, using template', [
                'error' => $e->getMessage(),
                'member_id' => $member->id,
            ]);

            return $this->generateTemplateMemberMessage($member, $channel, $context);
        }
    }

    /**
     * Generate and persist a message for a visitor.
     */
    public function createVisitorMessage(
        Visitor $visitor,
        FollowUpType $channel = FollowUpType::Sms
    ): AiGeneratedMessage {
        $message = $this->generateVisitorFollowUp($visitor, $channel);

        return AiGeneratedMessage::create([
            'branch_id' => $visitor->branch_id,
            'visitor_id' => $visitor->id,
            'message_type' => $message->messageType,
            'channel' => $channel,
            'generated_content' => $message->content,
            'context_used' => $message->context,
            'status' => AiMessageStatus::Pending,
            'ai_provider' => $message->provider,
            'ai_model' => $message->model,
            'tokens_used' => $message->tokensUsed,
        ]);
    }

    /**
     * Generate and persist a message for a member.
     */
    public function createMemberMessage(
        Member $member,
        FollowUpType $channel = FollowUpType::Sms
    ): AiGeneratedMessage {
        $message = $this->generateMemberReengagement($member, $channel);

        return AiGeneratedMessage::create([
            'branch_id' => $member->primary_branch_id,
            'member_id' => $member->id,
            'message_type' => $message->messageType,
            'channel' => $channel,
            'generated_content' => $message->content,
            'context_used' => $message->context,
            'status' => AiMessageStatus::Pending,
            'ai_provider' => $message->provider,
            'ai_model' => $message->model,
            'tokens_used' => $message->tokensUsed,
        ]);
    }

    /**
     * Build context array for a visitor.
     *
     * @return array<string, mixed>
     */
    protected function buildVisitorContext(Visitor $visitor): array
    {
        $lastAttendance = $visitor->attendance()->latest('date')->first();
        $successfulFollowUps = $visitor->followUps()
            ->whereIn('outcome', ['successful', 'callback', 'rescheduled'])
            ->count();

        return [
            'first_name' => $visitor->first_name,
            'last_name' => $visitor->last_name,
            'visit_date' => $visitor->visit_date?->format('M j, Y'),
            'days_since_visit' => $visitor->visit_date ? Carbon::parse($visitor->visit_date)->diffInDays(now()) : null,
            'how_heard' => $visitor->how_did_you_hear,
            'visit_count' => $visitor->attendance()->count(),
            'last_attendance_date' => $lastAttendance?->date?->format('M j, Y'),
            'successful_followups' => $successfulFollowUps,
            'follow_up_count' => $visitor->follow_up_count ?? 0,
        ];
    }

    /**
     * Build context array for a member.
     *
     * @return array<string, mixed>
     */
    protected function buildMemberContext(Member $member): array
    {
        $lastAttendance = $member->attendance()->latest('date')->first();
        $lastDonation = $member->donations()->latest('donation_date')->first();

        return [
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'member_since' => $member->joined_at?->format('M Y'),
            'last_attendance_date' => $lastAttendance?->date?->format('M j, Y'),
            'days_since_attendance' => $lastAttendance ? Carbon::parse($lastAttendance->date)->diffInDays(now()) : null,
            'last_donation_date' => $lastDonation?->donation_date?->format('M j, Y'),
            'is_baptized' => $member->baptized_at !== null,
            'churn_risk_score' => $member->churn_risk_score,
        ];
    }

    /**
     * Build the prompt for visitor follow-up.
     */
    protected function buildVisitorPrompt(Visitor $visitor, array $context, FollowUpType $channel): string
    {
        $charLimit = $channel === FollowUpType::Sms ? 160 : 500;

        $prompt = "Generate a {$channel->value} follow-up message for {$visitor->first_name} {$visitor->last_name}.\n\n";
        $prompt .= "Context:\n";
        $prompt .= "- First visit: {$context['visit_date']}\n";

        if ($context['days_since_visit']) {
            $prompt .= "- Days since first visit: {$context['days_since_visit']}\n";
        }

        if ($context['how_heard']) {
            $prompt .= "- How they heard about us: {$context['how_heard']}\n";
        }

        if ($context['visit_count'] > 1) {
            $prompt .= "- They have visited {$context['visit_count']} times\n";
        }

        if ($context['last_attendance_date'] && $context['last_attendance_date'] !== $context['visit_date']) {
            $prompt .= "- Last attended: {$context['last_attendance_date']}\n";
        }

        if ($context['successful_followups'] > 0) {
            $prompt .= "- Previous successful follow-ups: {$context['successful_followups']}\n";
        }

        $prompt .= "\nIMPORTANT: Keep the message under {$charLimit} characters.";

        return $prompt;
    }

    /**
     * Build the prompt for member re-engagement.
     */
    protected function buildMemberPrompt(Member $member, array $context, FollowUpType $channel): string
    {
        $charLimit = $channel === FollowUpType::Sms ? 160 : 500;

        $prompt = "Generate a {$channel->value} re-engagement message for {$member->first_name} {$member->last_name}.\n\n";
        $prompt .= "Context:\n";

        if ($context['member_since']) {
            $prompt .= "- Member since: {$context['member_since']}\n";
        }

        if ($context['last_attendance_date']) {
            $prompt .= "- Last attended: {$context['last_attendance_date']}\n";
        }

        if ($context['days_since_attendance']) {
            $prompt .= "- Days since last attendance: {$context['days_since_attendance']}\n";
        }

        if ($context['is_baptized']) {
            $prompt .= "- Baptized member\n";
        }

        if ($context['churn_risk_score'] && $context['churn_risk_score'] > 70) {
            $prompt .= "- Note: This member is at high risk of disengaging\n";
        }

        $prompt .= "\nIMPORTANT: Keep the message under {$charLimit} characters.";

        return $prompt;
    }

    /**
     * Generate a template-based follow-up message (fallback).
     */
    protected function generateTemplateFollowUp(
        Visitor $visitor,
        FollowUpType $channel,
        array $context
    ): GeneratedMessage {
        $templates = [
            'first_followup' => "Hi {name}! Thank you for visiting us. We'd love to see you again this Sunday. Let us know if you have any questions!",
            'return_visitor' => "Hi {name}! Great to see you've been back. We value your presence and hope to see you again soon!",
            'reminder' => "Hi {name}, we've missed you! Our doors are always open. Hope to see you this weekend!",
        ];

        $templateKey = match (true) {
            ($context['follow_up_count'] ?? 0) === 0 => 'first_followup',
            ($context['visit_count'] ?? 0) > 1 => 'return_visitor',
            default => 'reminder',
        };

        $content = str_replace('{name}', $visitor->first_name, $templates[$templateKey]);

        return new GeneratedMessage(
            content: $content,
            messageType: 'follow_up',
            channel: $channel->value,
            context: $context,
            provider: 'template',
            model: 'v1',
            tokensUsed: null,
        );
    }

    /**
     * Generate a template-based member message (fallback).
     */
    protected function generateTemplateMemberMessage(
        Member $member,
        FollowUpType $channel,
        array $context
    ): GeneratedMessage {
        $templates = [
            'high_risk' => "Hi {name}, we've missed you! Life gets busy, but we'd love to reconnect. Our doors are always open.",
            'medium_risk' => "Hi {name}, thinking of you! We'd love to see you this Sunday. Something special awaits!",
            'low_risk' => 'Hi {name}, just checking in! We value your presence and hope to see you soon.',
        ];

        $churnScore = $context['churn_risk_score'] ?? 0;
        $templateKey = match (true) {
            $churnScore > 70 => 'high_risk',
            $churnScore > 40 => 'medium_risk',
            default => 'low_risk',
        };

        $content = str_replace('{name}', $member->first_name, $templates[$templateKey]);

        return new GeneratedMessage(
            content: $content,
            messageType: 'reengagement',
            channel: $channel->value,
            context: $context,
            provider: 'template',
            model: 'v1',
            tokensUsed: null,
        );
    }
}
