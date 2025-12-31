<?php

namespace App\Enums;

enum EquipmentCategory: string
{
    case Audio = 'audio';
    case Video = 'video';
    case Musical = 'musical';
    case Furniture = 'furniture';
    case Computer = 'computer';
    case Lighting = 'lighting';
    case Other = 'other';
}
