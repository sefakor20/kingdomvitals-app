<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant\Branch;
use App\Services\EmailTemplateSeeder;
use App\Services\FollowUpTemplateSeeder;
use App\Services\SmsTemplateSeeder;
use Illuminate\Support\Facades\Log;

class BranchObserver
{
    public function __construct(
        protected FollowUpTemplateSeeder $followUpTemplateSeeder,
        protected EmailTemplateSeeder $emailTemplateSeeder,
        protected SmsTemplateSeeder $smsTemplateSeeder
    ) {}

    /**
     * Handle the Branch "created" event.
     *
     * Seeds default templates for follow-ups, emails, and SMS.
     */
    public function created(Branch $branch): void
    {
        Log::info('BranchObserver: created event fired', ['branch_id' => $branch->id]);

        $this->followUpTemplateSeeder->seedForBranch($branch);
        $this->emailTemplateSeeder->seedForBranch($branch);
        $this->smsTemplateSeeder->seedForBranch($branch);

        Log::info('BranchObserver: templates seeded', ['branch_id' => $branch->id]);
    }
}
