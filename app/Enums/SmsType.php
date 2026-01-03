<?php

namespace App\Enums;

enum SmsType: string
{
    case Birthday = 'birthday';
    case Reminder = 'reminder';
    case Welcome = 'welcome';
    case Announcement = 'announcement';
    case FollowUp = 'follow_up';
    case Custom = 'custom';
}
