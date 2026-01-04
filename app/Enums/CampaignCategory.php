<?php

namespace App\Enums;

enum CampaignCategory: string
{
    case BuildingFund = 'building_fund';
    case Missions = 'missions';
    case SpecialProject = 'special_project';
    case Welfare = 'welfare';
    case Equipment = 'equipment';
    case Other = 'other';
}
