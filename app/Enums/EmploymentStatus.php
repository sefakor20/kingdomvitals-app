<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Employed = 'employed';
    case SelfEmployed = 'self_employed';
    case Unemployed = 'unemployed';
    case Student = 'student';
    case Retired = 'retired';
}
