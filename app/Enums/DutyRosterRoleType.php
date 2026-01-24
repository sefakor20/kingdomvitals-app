<?php

declare(strict_types=1);

namespace App\Enums;

enum DutyRosterRoleType: string
{
    case Preacher = 'preacher';
    case Liturgist = 'liturgist';
    case Reader = 'reader';

    public function label(): string
    {
        return match ($this) {
            self::Preacher => 'Preacher',
            self::Liturgist => 'Liturgist',
            self::Reader => 'Reader',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Preacher => 'Preachers',
            self::Liturgist => 'Liturgists',
            self::Reader => 'Readers',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Preacher => 'microphone',
            self::Liturgist => 'book-open',
            self::Reader => 'document-text',
        };
    }
}
