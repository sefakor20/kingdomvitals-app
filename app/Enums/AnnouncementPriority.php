<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementPriority: string
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Important => 'Important',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'zinc',
            self::Important => 'yellow',
            self::Urgent => 'red',
        };
    }

    public function emailSubjectPrefix(): string
    {
        return match ($this) {
            self::Normal => '',
            self::Important => '[IMPORTANT] ',
            self::Urgent => '[URGENT] ',
        };
    }
}
