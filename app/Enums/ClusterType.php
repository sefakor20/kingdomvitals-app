<?php

namespace App\Enums;

enum ClusterType: string
{
    case CellGroup = 'cell_group';
    case HouseFellowship = 'house_fellowship';
    case Zone = 'zone';
    case District = 'district';
}
