<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClusterHealthAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<array{cluster_id: string, cluster_name: string, health_level: string, health_score: float, top_concerns: array, primary_recommendation: ?string}>  $clusters
     */
    public function __construct(
        public array $clusters,
        public string $branchId
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
        $count = count($this->clusters);
        $criticalCount = collect($this->clusters)->where('health_level', 'critical')->count();

        $subject = $criticalCount > 0
            ? "Cluster Alert: {$criticalCount} cluster(s) in critical condition"
            : "Cluster Health Alert: {$count} cluster(s) need attention";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!");

        if ($criticalCount > 0) {
            $mail->error();
        }

        $mail->line('The following clusters have been identified as needing leadership attention:')
            ->line('');

        foreach ($this->clusters as $cluster) {
            $healthLevel = $this->formatHealthLevel($cluster['health_level']);
            $score = round($cluster['health_score'], 1);

            $mail->line("### {$cluster['cluster_name']}")
                ->line("**Status:** {$healthLevel} (Score: {$score}/100)");

            if (! empty($cluster['top_concerns'])) {
                $concerns = collect($cluster['top_concerns'])
                    ->map(fn ($score, $area): string => ucfirst($area).": {$score}")
                    ->join(', ');
                $mail->line("**Areas of Concern:** {$concerns}");
            }

            if ($cluster['primary_recommendation']) {
                $mail->line("**Recommendation:** {$cluster['primary_recommendation']}");
            }

            $mail->line('');
        }

        return $mail
            ->action('View Clusters', url("/branches/{$this->branchId}/clusters"))
            ->line('Please review these clusters and take appropriate action to improve their health.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cluster_health_alert',
            'branch_id' => $this->branchId,
            'clusters' => $this->clusters,
            'count' => count($this->clusters),
            'critical_count' => collect($this->clusters)->where('health_level', 'critical')->count(),
            'message' => $this->getSummaryMessage(),
        ];
    }

    protected function formatHealthLevel(string $level): string
    {
        return match ($level) {
            'thriving' => 'Thriving',
            'healthy' => 'Healthy',
            'stable' => 'Stable',
            'struggling' => 'Struggling',
            'critical' => 'Critical',
            default => ucfirst($level),
        };
    }

    protected function getSummaryMessage(): string
    {
        $count = count($this->clusters);
        $criticalCount = collect($this->clusters)->where('health_level', 'critical')->count();

        if ($criticalCount > 0) {
            return "{$criticalCount} cluster(s) are in critical condition and require immediate attention.";
        }

        return "{$count} cluster(s) are struggling and may need leadership support.";
    }
}
