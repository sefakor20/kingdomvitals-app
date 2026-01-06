<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin\Plans;

use App\Models\SubscriptionPlan;
use Illuminate\View\View;
use Livewire\Component;

class PlanIndex extends Component
{
    public function render(): View
    {
        return view('livewire.super-admin.plans.plan-index', [
            'plans' => SubscriptionPlan::orderBy('price_monthly')->get(),
        ])->layout('components.layouts.superadmin.app');
    }
}
