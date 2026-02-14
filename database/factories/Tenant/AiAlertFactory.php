<?php

declare(strict_types=1);

namespace Database\Factories\Tenant;

use App\Enums\AiAlertType;
use App\Enums\AlertSeverity;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\AiAlert>
 */
class AiAlertFactory extends Factory
{
    protected $model = AiAlert::class;

    public function definition(): array
    {
        $alertType = fake()->randomElement(AiAlertType::cases());
        $severity = fake()->randomElement(AlertSeverity::cases());

        return [
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $this->getTitle($alertType),
            'description' => $this->getDescription($alertType),
            'alertable_type' => Member::class,
            'alertable_id' => fake()->uuid(),
            'data' => [],
            'is_read' => false,
            'is_acknowledged' => false,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ];
    }

    protected function getTitle(AiAlertType $type): string
    {
        return match ($type) {
            AiAlertType::ChurnRisk => 'High churn risk detected for '.fake()->name(),
            AiAlertType::AttendanceAnomaly => 'Attendance anomaly detected for '.fake()->name(),
            AiAlertType::LifecycleChange => 'Lifecycle transition: '.fake()->name().' is now At Risk',
            AiAlertType::CriticalPrayer => 'Critical prayer request requires attention',
            AiAlertType::ClusterHealth => "Cluster '".fake()->word()."' health is critical",
            AiAlertType::HouseholdDisengagement => "Household '".fake()->lastName()."' is disengaged",
        };
    }

    protected function getDescription(AiAlertType $type): string
    {
        return match ($type) {
            AiAlertType::ChurnRisk => 'Member has a churn risk score exceeding the threshold.',
            AiAlertType::AttendanceAnomaly => 'Member shows a significant attendance pattern change.',
            AiAlertType::LifecycleChange => 'Member has transitioned to an at-risk lifecycle stage.',
            AiAlertType::CriticalPrayer => 'A critical prayer request needs pastoral attention.',
            AiAlertType::ClusterHealth => 'Cluster health has declined significantly.',
            AiAlertType::HouseholdDisengagement => 'Household engagement has dropped to disengaged level.',
        };
    }

    public function churnRisk(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::ChurnRisk,
            'severity' => AlertSeverity::High,
        ]);
    }

    public function attendanceAnomaly(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::AttendanceAnomaly,
            'severity' => AlertSeverity::Medium,
        ]);
    }

    public function lifecycleChange(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::LifecycleChange,
            'severity' => AlertSeverity::High,
        ]);
    }

    public function criticalPrayer(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::CriticalPrayer,
            'severity' => AlertSeverity::Critical,
        ]);
    }

    public function clusterHealth(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::ClusterHealth,
            'severity' => AlertSeverity::High,
        ]);
    }

    public function householdDisengagement(): static
    {
        return $this->state(fn () => [
            'alert_type' => AiAlertType::HouseholdDisengagement,
            'severity' => AlertSeverity::Medium,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn () => [
            'severity' => AlertSeverity::Critical,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn () => [
            'severity' => AlertSeverity::High,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn () => [
            'severity' => AlertSeverity::Medium,
        ]);
    }

    public function low(): static
    {
        return $this->state(fn () => [
            'severity' => AlertSeverity::Low,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'is_read' => true,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn () => [
            'is_read' => false,
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(fn () => [
            'is_acknowledged' => true,
            'acknowledged_by' => fake()->uuid(),
            'acknowledged_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function unacknowledged(): static
    {
        return $this->state(fn () => [
            'is_acknowledged' => false,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
        ]);
    }
}
