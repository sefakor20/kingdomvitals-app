<?php

namespace App\Enums;

enum ServiceType: string
{
    case Sunday = 'sunday';
    case Midweek = 'midweek';
    case Prayer = 'prayer';
    case Youth = 'youth';
    case Children = 'children';
    case Special = 'special';
}
