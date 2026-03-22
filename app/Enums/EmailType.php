<?php

namespace App\Enums;

enum EmailType: string
{
    case Birthday = 'birthday';
    case Reminder = 'reminder';
    case Welcome = 'welcome';
    case Announcement = 'announcement';
    case FollowUp = 'follow_up';
    case Newsletter = 'newsletter';
    case EventReminder = 'event_reminder';
    case Custom = 'custom';
}
