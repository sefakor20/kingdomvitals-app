<?php

declare(strict_types=1);

namespace App\Services\AI\Chatbot;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Event;

class EventsHandler
{
    /**
     * Handle upcoming events request.
     *
     * @param  array<string, mixed>  $entities
     */
    public function handle(Branch $branch, array $entities = []): string
    {
        $events = Event::query()
            ->where('branch_id', $branch->id)
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        if ($events->isEmpty()) {
            return 'No upcoming events scheduled at this time. Check back soon!';
        }

        $response = "Upcoming events:\n";

        foreach ($events as $event) {
            $date = $event->starts_at->format('M j');
            $time = $event->starts_at->format('g:ia');
            $response .= "\n• {$event->name}\n  {$date} at {$time}";

            if ($event->location) {
                $response .= "\n  @ {$event->location}";
            }
        }

        return $response;
    }
}
