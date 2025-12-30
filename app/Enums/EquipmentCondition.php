<?php

namespace App\Enums;

enum EquipmentCondition: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case OutOfService = 'out_of_service';
}
