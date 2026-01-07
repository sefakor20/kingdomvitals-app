<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case PartiallyFailed = 'partially_failed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::PartiallyFailed => 'Partially Failed',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Scheduled => 'blue',
            self::Sending => 'yellow',
            self::Sent => 'green',
            self::PartiallyFailed => 'orange',
            self::Failed => 'red',
        };
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeSent(): bool
    {
        return $this === self::Draft || $this === self::Scheduled;
    }

    public function canBeDeleted(): bool
    {
        return $this !== self::Sending;
    }
}
