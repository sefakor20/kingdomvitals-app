<?php

declare(strict_types=1);

namespace App\Notifications\AI;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Models\Tenant\AiAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DailyAiAlertDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, AiAlert>  $alerts
     */
    public function __construct(
        public Collection $alerts,
        public string $branchId,
        public string $branchName
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
        $criticalCount = $this->alerts->where('severity', AlertSeverity::Critical)->count();
        $highCount = $this->alerts->where('severity', AlertSeverity::High)->count();

        $mail = (new MailMessage)
            ->subject($this->getSubject($criticalCount, $highCount))
            ->greeting("Hello {$notifiable->name}!");

        if ($criticalCount > 0) {
            $mail->error();
        }

        $mail->line("Here's your daily AI insights digest for **{$this->branchName}**:")
            ->line('');

        // Summary stats
        $mail->line('### Summary')
            ->line("- **Total Alerts:** {$this->alerts->count()}")
            ->line("- **Critical:** {$criticalCount}")
            ->line("- **High:** {$highCount}")
            ->line("- **Medium:** {$this->alerts->where('severity', AlertSeverity::Medium)->count()}")
            ->line("- **Low:** {$this->alerts->where('severity', AlertSeverity::Low)->count()}")
            ->line('');

        // Group alerts by type
        $byType = $this->alerts->groupBy(fn (AiAlert $alert) => $alert->alert_type->value);

        foreach ($byType as $typeValue => $typeAlerts) {
            $type = AiAlertType::tryFrom($typeValue);
            if (! $type) {
                continue;
            }

            $mail->line("### {$type->label()} ({$typeAlerts->count()})")
                ->line('');

            // Show up to 5 alerts per type
            foreach ($typeAlerts->take(5) as $alert) {
                $severityLabel = $alert->severity->label();
                $mail->line("- [{$severityLabel}] {$alert->title}");
            }

            if ($typeAlerts->count() > 5) {
                $remaining = $typeAlerts->count() - 5;
                $mail->line("- *...and {$remaining} more*");
            }

            $mail->line('');
        }

        return $mail
            ->action('View All Alerts', url("/branches/{$this->branchId}/ai-insights"))
            ->line('Review and acknowledge these alerts to keep your ministry running smoothly.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bySeverity = [];
        foreach (AlertSeverity::cases() as $severity) {
            $bySeverity[$severity->value] = $this->alerts->where('severity', $severity)->count();
        }

        $byType = [];
        foreach (AiAlertType::cases() as $type) {
            $count = $this->alerts->where('alert_type', $type)->count();
            if ($count > 0) {
                $byType[$type->value] = $count;
            }
        }

        return [
            'type' => 'ai_alert_digest',
            'branch_id' => $this->branchId,
            'branch_name' => $this->branchName,
            'total_alerts' => $this->alerts->count(),
            'by_severity' => $bySeverity,
            'by_type' => $byType,
            'message' => $this->getSummaryMessage(),
        ];
    }

    protected function getSubject(int $criticalCount, int $highCount): string
    {
        $total = $this->alerts->count();

        if ($criticalCount > 0) {
            return "[CRITICAL] Daily AI Digest: {$criticalCount} critical alert(s) for {$this->branchName}";
        }

        if ($highCount > 0) {
            return "Daily AI Digest: {$highCount} high priority alert(s) for {$this->branchName}";
        }

        return "Daily AI Digest: {$total} alert(s) for {$this->branchName}";
    }

    protected function getSummaryMessage(): string
    {
        $total = $this->alerts->count();
        $criticalCount = $this->alerts->where('severity', AlertSeverity::Critical)->count();

        if ($criticalCount > 0) {
            return "{$criticalCount} critical alert(s) require immediate attention out of {$total} total.";
        }

        return "{$total} AI-generated alert(s) from the last 24 hours.";
    }
}
