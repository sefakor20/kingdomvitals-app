<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Services\ActivityLoggingService;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function __construct(
        private ActivityLoggingService $loggingService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user) {
            return;
        }

        // Get the user's primary branch
        $primaryAccess = $user->branchAccess()->with('branch')->first();

        if (! $primaryAccess || ! $primaryAccess->branch) {
            return;
        }

        $this->loggingService->logAuthEvent(
            branchId: $primaryAccess->branch->id,
            event: ActivityEvent::Logout,
            userId: $user->id,
            userName: $user->name,
        );
    }
}
