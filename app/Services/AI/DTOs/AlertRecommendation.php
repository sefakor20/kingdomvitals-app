<?php

declare(strict_types=1);

namespace App\Services\AI\DTOs;

readonly class AlertRecommendation
{
    public function __construct(
        public string $action,
        public string $description,
        public string $priority,
        public ?string $assignTo = null,
        public ?string $icon = null,
    ) {}

    /**
     * Get the priority label for display.
     */
    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'immediate' => 'Immediate',
            'soon' => 'Within 48 Hours',
            'when_possible' => 'When Possible',
            default => 'Standard',
        };
    }

    /**
     * Get the priority color for UI display.
     */
    public function priorityColor(): string
    {
        return match ($this->priority) {
            'immediate' => 'red',
            'soon' => 'amber',
            'when_possible' => 'zinc',
            default => 'zinc',
        };
    }

    /**
     * Get the priority icon.
     */
    public function priorityIcon(): string
    {
        return match ($this->priority) {
            'immediate' => 'exclamation-circle',
            'soon' => 'clock',
            'when_possible' => 'check-circle',
            default => 'minus-circle',
        };
    }

    /**
     * Get the assigned role label.
     */
    public function assignToLabel(): ?string
    {
        if ($this->assignTo === null) {
            return null;
        }

        return match ($this->assignTo) {
            'pastor' => 'Pastor',
            'leader' => 'Leader',
            'care_team' => 'Care Team',
            'prayer_team' => 'Prayer Team',
            'admin' => 'Administrator',
            default => ucfirst($this->assignTo),
        };
    }

    /**
     * Check if this is an immediate priority.
     */
    public function isImmediate(): bool
    {
        return $this->priority === 'immediate';
    }

    /**
     * Check if this needs to be done soon.
     */
    public function isSoon(): bool
    {
        return $this->priority === 'soon';
    }

    /**
     * Get the effective icon (custom or default based on priority).
     */
    public function effectiveIcon(): string
    {
        return $this->icon ?? $this->priorityIcon();
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'description' => $this->description,
            'priority' => $this->priority,
            'assign_to' => $this->assignTo,
            'icon' => $this->icon,
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            action: $data['action'],
            description: $data['description'],
            priority: $data['priority'],
            assignTo: $data['assign_to'] ?? null,
            icon: $data['icon'] ?? null,
        );
    }
}
