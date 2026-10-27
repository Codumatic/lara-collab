<?php

namespace App\Enums;

enum TestCaseStatus: string
{
    use HasLabelOptions;

    case NOT_RUN = 'not_run';
    case PASSED = 'passed';
    case FAILED = 'failed';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NOT_RUN => 'Not run',
            self::PASSED => 'Passed',
            self::FAILED => 'Failed',
            self::BLOCKED => 'Blocked',
        };
    }
}
