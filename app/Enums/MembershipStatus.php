<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Deceased = 'deceased';
    case Transferred = 'transferred';
}
