<?php

declare(strict_types=1);

namespace App\Services\AI\Chatbot;

use App\Enums\PrayerRequestStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;
use App\Models\Tenant\PrayerRequest;

class PrayerRequestHandler
{
    /**
     * Handle prayer request submission.
     *
     * @param  array<string, mixed>  $entities
     */
    public function handle(?Member $member, Branch $branch, array $entities = []): string
    {
        $prayerSubject = $entities['prayer_subject'] ?? null;

        // If no specific prayer content, prompt for it
        if (! $prayerSubject) {
            return "I'd be happy to submit a prayer request for you. Please reply with what you'd like prayer for.";
        }

        // Create the prayer request
        try {
            PrayerRequest::create([
                'branch_id' => $branch->id,
                'member_id' => $member?->id,
                'title' => 'Prayer Request via Chatbot',
                'description' => $prayerSubject,
                'submitter_name' => $member?->fullName() ?? 'Anonymous',
                'is_anonymous' => $member === null,
                'is_public' => false,
                'status' => PrayerRequestStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $name = $member?->first_name ?? 'friend';

            return "Thank you, {$name}. Your prayer request has been submitted. Our prayer team will be praying for you.";
        } catch (\Throwable $e) {
            return "I'm sorry, there was an issue submitting your prayer request. Please try again or contact the church office.";
        }
    }
}
