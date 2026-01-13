<?php

namespace App\Notifications;

use App\Models\Tenant\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetThresholdNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Budget $budget,
        public string $alertLevel,
        public float $utilizationPercent
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
        $subject = match ($this->alertLevel) {
            'warning' => "Budget Alert: {$this->budget->name} at {$this->utilizationPercent}%",
            'critical' => "Budget Critical: {$this->budget->name} at {$this->utilizationPercent}%",
            'exceeded' => "Budget Exceeded: {$this->budget->name} is over budget!",
            default => "Budget Notification: {$this->budget->name}",
        };

        $allocated = number_format((float) $this->budget->allocated_amount, 2);
        $spent = number_format($this->budget->actual_spending, 2);
        $remaining = number_format($this->budget->remaining_amount, 2);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->getAlertMessage());

        if ($this->alertLevel === 'exceeded') {
            $mail->error();
        }

        return $mail
            ->line("**Budget:** {$this->budget->name}")
            ->line('**Category:** '.ucfirst(str_replace('_', ' ', $this->budget->category->value)))
            ->line("**Allocated:** GHS {$allocated}")
            ->line("**Spent:** GHS {$spent}")
            ->line("**Remaining:** GHS {$remaining}")
            ->line("**Utilization:** {$this->utilizationPercent}%")
            ->action('View Budgets', url("/branches/{$this->budget->branch_id}/budgets"))
            ->line('Please review your spending and take appropriate action.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'branch_id' => $this->budget->branch_id,
            'category' => $this->budget->category->value,
            'alert_level' => $this->alertLevel,
            'utilization_percent' => $this->utilizationPercent,
            'allocated_amount' => $this->budget->allocated_amount,
            'actual_spending' => $this->budget->actual_spending,
            'remaining_amount' => $this->budget->remaining_amount,
            'message' => $this->getAlertMessage(),
        ];
    }

    private function getAlertMessage(): string
    {
        return match ($this->alertLevel) {
            'exceeded' => "The budget \"{$this->budget->name}\" has exceeded its allocated amount! Current utilization is at {$this->utilizationPercent}%.",
            'critical' => "The budget \"{$this->budget->name}\" is critically close to its limit at {$this->utilizationPercent}% utilization.",
            'warning' => "The budget \"{$this->budget->name}\" has reached {$this->utilizationPercent}% of its allocated amount.",
            default => "Budget alert for \"{$this->budget->name}\".",
        };
    }
}
