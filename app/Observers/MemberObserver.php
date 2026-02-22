<?php

namespace App\Observers;

use App\Jobs\SendWelcomeSmsJob;
use App\Models\Tenant\Member;
use Illuminate\Database\Eloquent\Model;

class MemberObserver extends BaseAuditObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Model $model): void
    {
        // Call parent to log activity
        parent::created($model);

        // Dispatch welcome SMS job
        if ($model instanceof Member) {
            SendWelcomeSmsJob::dispatch($model->id);
        }
    }
}
