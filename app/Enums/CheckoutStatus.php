<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckoutStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
