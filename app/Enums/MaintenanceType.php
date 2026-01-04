<?php

declare(strict_types=1);

namespace App\Enums;

enum MaintenanceType: string
{
    case Scheduled = 'scheduled';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Upgrade = 'upgrade';
    case Emergency = 'emergency';
}
