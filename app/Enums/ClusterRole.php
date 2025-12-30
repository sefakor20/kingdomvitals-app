<?php

namespace App\Enums;

enum ClusterRole: string
{
    case Leader = 'leader';
    case Assistant = 'assistant';
    case Member = 'member';
}
