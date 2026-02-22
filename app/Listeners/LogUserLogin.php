<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Services\ActivityLoggingService;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function __construct(
        private ActivityLoggingService $loggingService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Get the user's primary branch
        $primaryAccess = $user->branchAccess()->with('branch')->first();

        if (! $primaryAccess || ! $primaryAccess->branch) {
            return;
        }

        $this->loggingService->logAuthEvent(
            branchId: $primaryAccess->branch->id,
            event: ActivityEvent::Login,
            userId: $user->id,
            userName: $user->name,
        );
    }
}
