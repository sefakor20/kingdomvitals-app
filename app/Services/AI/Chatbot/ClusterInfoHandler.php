<?php

declare(strict_types=1);

namespace App\Services\AI\Chatbot;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;

class ClusterInfoHandler
{
    /**
     * Handle cluster/small group info request.
     *
     * @param  array<string, mixed>  $entities
     */
    public function handle(?Member $member, Branch $branch, array $entities = []): string
    {
        if (! $member) {
            return "I couldn't find your member profile. Please contact the church office for small group information.";
        }

        $cluster = $member->clusters()->first();

        if (! $cluster) {
            return "Hi {$member->first_name}! You're not currently assigned to a small group. Contact the church office to join one.";
        }

        $response = "Your small group info:\n\n";
        $response .= "Group: {$cluster->name}\n";

        if ($cluster->leader) {
            $response .= "Leader: {$cluster->leader->fullName()}\n";
        }

        if ($cluster->meeting_day) {
            $response .= "Meets: {$cluster->meeting_day}";
            if ($cluster->meeting_time) {
                $response .= " at {$cluster->meeting_time}";
            }
            $response .= "\n";
        }

        if ($cluster->meeting_location) {
            $response .= "Location: {$cluster->meeting_location}";
        }

        // Get next meeting if available
        $nextMeeting = $cluster->meetings()
            ->where('meeting_date', '>=', now()->startOfDay())
            ->orderBy('meeting_date')
            ->first();

        if ($nextMeeting) {
            $response .= "\n\nNext meeting: ".$nextMeeting->meeting_date->format('M j');
        }

        return $response;
    }
}
