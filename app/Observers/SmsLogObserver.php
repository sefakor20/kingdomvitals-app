<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant\SmsLog;
use App\Services\PlanAccessService;

class SmsLogObserver
{
    /**
     * Handle the SmsLog "created" event.
     */
    public function created(SmsLog $smsLog): void
    {
        app(PlanAccessService::class)->invalidateCountCache('sms');
    }

    /**
     * Handle the SmsLog "deleted" event.
     */
    public function deleted(SmsLog $smsLog): void
    {
        app(PlanAccessService::class)->invalidateCountCache('sms');
    }
}
