<?php

namespace App\Enums;

enum IssueStatus: string
{
    use HasLabelOptions;

    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case DEPLOYED = 'deployed';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::IN_PROGRESS => 'In progress',
            self::RESOLVED => 'Resolved',
            self::DEPLOYED => 'Deployed',
            self::CLOSED => 'Closed',
        };
    }
}
