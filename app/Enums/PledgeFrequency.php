<?php

namespace App\Enums;

enum PledgeFrequency: string
{
    case OneTime = 'one_time';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
