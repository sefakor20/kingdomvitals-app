<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Partial = 'partial';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Partial => 'Partial',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::Paid => 'green',
            self::Partial => 'yellow',
            self::Overdue => 'red',
            self::Cancelled => 'zinc',
            self::Refunded => 'purple',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Partial, self::Overdue]);
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeSent(): bool
    {
        return $this === self::Draft;
    }

    public function canReceivePayment(): bool
    {
        return in_array($this, [self::Sent, self::Partial, self::Overdue]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Partial, self::Overdue]);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::Paid;
    }
}
