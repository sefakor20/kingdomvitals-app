<?php

declare(strict_types=1);

namespace App\Enums;

enum EventVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case InviteOnly = 'invite_only';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::MembersOnly => 'Members Only',
            self::InviteOnly => 'Invite Only',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Anyone can view and register',
            self::MembersOnly => 'Only church members can view and register',
            self::InviteOnly => 'Only invited guests can register',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Public => 'globe-alt',
            self::MembersOnly => 'users',
            self::InviteOnly => 'envelope',
        };
    }
}
