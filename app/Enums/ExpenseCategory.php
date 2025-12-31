<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Utilities = 'utilities';
    case Salaries = 'salaries';
    case Maintenance = 'maintenance';
    case Supplies = 'supplies';
    case Events = 'events';
    case Missions = 'missions';
    case Transport = 'transport';
    case Other = 'other';
}
