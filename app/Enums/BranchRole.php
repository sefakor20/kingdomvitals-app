<?php

namespace App\Enums;

enum BranchRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';
    case Volunteer = 'volunteer';
}
