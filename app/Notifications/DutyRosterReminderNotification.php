<?php

namespace App\Notifications;

use App\Models\Tenant\DutyRoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DutyRosterReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DutyRoster $dutyRoster,
        public string $role
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceDate = $this->dutyRoster->service_date->format('l, F j, Y');
        $branchName = $this->dutyRoster->branch->name;
        $roleName = ucfirst($this->role);

        return (new MailMessage)
            ->subject(__('Duty Roster Reminder: :role on :date', [
                'role' => $roleName,
                'date' => $this->dutyRoster->service_date->format('M j, Y'),
            ]))
            ->greeting(__('Hello!'))
            ->line(__('This is a reminder that you are assigned as **:role** for the upcoming service.', [
                'role' => $roleName,
            ]))
            ->line(__('**Date:** :date', ['date' => $serviceDate]))
            ->line(__('**Branch:** :branch', ['branch' => $branchName]))
            ->when($this->dutyRoster->theme, fn ($mail) => $mail->line(__('**Theme:** :theme', ['theme' => $this->dutyRoster->theme])))
            ->when($this->dutyRoster->service, fn ($mail) => $mail->line(__('**Service:** :service', ['service' => $this->dutyRoster->service->name])))
            ->line(__('Please ensure you are prepared for your role.'))
            ->line(__('If you have any questions or conflicts, please contact the church office.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'duty_roster_id' => $this->dutyRoster->id,
            'service_date' => $this->dutyRoster->service_date->toISOString(),
            'role' => $this->role,
            'branch_id' => $this->dutyRoster->branch_id,
            'branch_name' => $this->dutyRoster->branch->name,
            'message' => __('Reminder: You are assigned as :role on :date', [
                'role' => $this->role,
                'date' => $this->dutyRoster->service_date->format('M j, Y'),
            ]),
        ];
    }
}
