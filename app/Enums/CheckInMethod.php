<?php

namespace App\Enums;

enum CheckInMethod: string
{
    case Manual = 'manual';
    case Qr = 'qr';
    case Kiosk = 'kiosk';
    case Mobile = 'mobile';
}
