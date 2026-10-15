<?php

namespace App\Enums;

enum IssuePriority: string
{
    use HasLabelOptions;

    case URGENT = 'urgent';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case LOWEST = 'lowest';

    public function label(): string
    {
        return match ($this) {
            self::URGENT => 'Urgent',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::LOWEST => 'Lowest',
        };
    }
}
