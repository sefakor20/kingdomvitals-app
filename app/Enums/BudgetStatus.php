<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
}
