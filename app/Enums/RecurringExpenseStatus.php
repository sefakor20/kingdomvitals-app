<?php

namespace App\Enums;

enum RecurringExpenseStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
}
