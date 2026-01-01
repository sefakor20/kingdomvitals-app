<?php

declare(strict_types=1);

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Sms = 'sms';
    case Email = 'email';
    case Visit = 'visit';
    case WhatsApp = 'whatsapp';
    case Other = 'other';
}
