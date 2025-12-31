<?php

namespace App\Enums;

enum DonationType: string
{
    case Tithe = 'tithe';
    case Offering = 'offering';
    case BuildingFund = 'building_fund';
    case Missions = 'missions';
    case Special = 'special';
    case Welfare = 'welfare';
    case Other = 'other';
}
