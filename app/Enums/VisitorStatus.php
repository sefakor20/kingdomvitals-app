<?php

namespace App\Enums;

enum VisitorStatus: string
{
    case New = 'new';
    case FollowedUp = 'followed_up';
    case Returning = 'returning';
    case Converted = 'converted';
    case NotInterested = 'not_interested';
}
