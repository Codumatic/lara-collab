<?php

namespace App\Enums;

enum TaskStatus: string
{
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

    /**
     * Board columns, one per status, in workflow order.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public static function columns(): array
    {
        return array_map(
            fn (self $status): array => ['id' => $status->value, 'name' => $status->label()],
            self::cases(),
        );
    }
}
