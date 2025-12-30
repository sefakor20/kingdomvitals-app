<?php

namespace App\Enums;

enum PledgeStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Paused = 'paused';
}
