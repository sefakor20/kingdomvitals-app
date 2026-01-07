<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformPaymentMethod: string
{
    case Paystack = 'paystack';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Paystack',
            self::BankTransfer => 'Bank Transfer',
            self::Cash => 'Cash',
            self::Cheque => 'Cheque',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Paystack => 'credit-card',
            self::BankTransfer => 'building-library',
            self::Cash => 'banknotes',
            self::Cheque => 'document-text',
        };
    }
}
