<?php

namespace App\Enums;

enum Severity: string
{
    case CRITICAL = 'critical';
    case MAJOR = 'major';
    case MEDIUM = 'medium';
    case LOW = 'low';
}
