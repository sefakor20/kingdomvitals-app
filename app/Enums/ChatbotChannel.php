<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatbotChannel: string
{
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::WhatsApp => 'WhatsApp',
        };
    }

    public function maxMessageLength(): int
    {
        return match ($this) {
            self::Sms => 160,
            self::WhatsApp => 4096,
        };
    }
}
