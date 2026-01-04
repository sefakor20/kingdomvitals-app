<?php

namespace App\Enums;

enum HouseholdRole: string
{
    case Head = 'head';
    case Spouse = 'spouse';
    case Child = 'child';
    case Other = 'other';
}
