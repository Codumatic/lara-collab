<?php

namespace App\Enums;

enum IssueSeverity: string
{
    use HasLabelOptions;

    case CRITICAL = 'critical';
    case MAJOR = 'major';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::MAJOR => 'Major',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
        };
    }
}
