<?php

declare(strict_types=1);

namespace App\Livewire\Branches;

use App\Enums\AiAlertType;
use App\Models\Tenant\AiAlertSetting;
use App\Models\Tenant\Branch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AlertSettings extends Component
{
    public Branch $branch;

    /** @var array<string, array<string, mixed>> */
    public array $settings = [];

    /** @var array<string, bool> */
    public array $expanded = [];

    public function mount(Branch $branch): void
    {
        $this->authorize('update', $branch);
        $this->branch = $branch;
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        AiAlertSetting::initializeForBranch($this->branch->id);

        $settings = AiAlertSetting::forBranch($this->branch->id)->get();

        foreach ($settings as $setting) {
            $alertType = $setting->alert_type->value;

            $this->settings[$alertType] = [
                'id' => $setting->id,
                'is_enabled' => $setting->is_enabled,
                'threshold_value' => $setting->threshold_value,
                'cooldown_hours' => $setting->cooldown_hours,
                'notification_channels' => $setting->notification_channels ?? ['database'],
                'recipient_roles' => $setting->recipient_roles ?? ['admin', 'pastor'],
                'last_triggered_at' => $setting->last_triggered_at?->diffForHumans(),
            ];

            $this->expanded[$alertType] = false;
        }
    }

    public function toggleExpanded(string $alertType): void
    {
        $this->expanded[$alertType] = ! ($this->expanded[$alertType] ?? false);
    }

    public function save(): void
    {
        $this->authorize('update', $this->branch);

        foreach ($this->settings as $alertType => $data) {
            AiAlertSetting::where('id', $data['id'])->update([
                'is_enabled' => $data['is_enabled'],
                'threshold_value' => $data['threshold_value'],
                'cooldown_hours' => (int) $data['cooldown_hours'],
                'notification_channels' => $data['notification_channels'],
                'recipient_roles' => $data['recipient_roles'],
            ]);
        }

        $this->dispatch('settings-saved');
    }

    public function resetToDefaults(string $alertType): void
    {
        $type = AiAlertType::from($alertType);

        $this->settings[$alertType] = [
            ...$this->settings[$alertType],
            'is_enabled' => true,
            'threshold_value' => $type->defaultThreshold(),
            'cooldown_hours' => $type->defaultCooldownHours(),
            'notification_channels' => ['database', 'mail'],
            'recipient_roles' => ['admin', 'pastor'],
        ];
    }

    public function toggleChannel(string $alertType, string $channel): void
    {
        $channels = $this->settings[$alertType]['notification_channels'] ?? [];

        if (in_array($channel, $channels, true)) {
            $this->settings[$alertType]['notification_channels'] = array_values(
                array_filter($channels, fn ($c) => $c !== $channel)
            );
        } else {
            $this->settings[$alertType]['notification_channels'] = [...$channels, $channel];
        }
    }

    public function toggleRole(string $alertType, string $role): void
    {
        $roles = $this->settings[$alertType]['recipient_roles'] ?? [];

        if (in_array($role, $roles, true)) {
            $this->settings[$alertType]['recipient_roles'] = array_values(
                array_filter($roles, fn ($r) => $r !== $role)
            );
        } else {
            $this->settings[$alertType]['recipient_roles'] = [...$roles, $role];
        }
    }

    /**
     * @return array<string, AiAlertType>
     */
    #[Computed]
    public function alertTypes(): array
    {
        $types = [];
        foreach (AiAlertType::cases() as $type) {
            $types[$type->value] = $type;
        }

        return $types;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    #[Computed]
    public function cooldownOptions(): array
    {
        return [
            ['value' => 0, 'label' => __('Immediate (no cooldown)')],
            ['value' => 1, 'label' => __('1 hour')],
            ['value' => 6, 'label' => __('6 hours')],
            ['value' => 24, 'label' => __('24 hours (1 day)')],
            ['value' => 72, 'label' => __('72 hours (3 days)')],
            ['value' => 168, 'label' => __('168 hours (7 days)')],
            ['value' => 336, 'label' => __('336 hours (14 days)')],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function notificationChannels(): array
    {
        return [
            'database' => __('In-App'),
            'mail' => __('Email'),
            'sms' => __('SMS'),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function recipientRoles(): array
    {
        return [
            'admin' => __('Administrators'),
            'pastor' => __('Pastors'),
            'staff' => __('Staff'),
            'leader' => __('Leaders'),
        ];
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.branches.alert-settings');
    }
}
