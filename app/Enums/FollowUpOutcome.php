<?php

declare(strict_types=1);

namespace App\Enums;

enum FollowUpOutcome: string
{
    case Successful = 'successful';
    case NoAnswer = 'no_answer';
    case Voicemail = 'voicemail';
    case Callback = 'callback';
    case NotInterested = 'not_interested';
    case WrongNumber = 'wrong_number';
    case Rescheduled = 'rescheduled';
    case Pending = 'pending';
}
