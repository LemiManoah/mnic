<?php

declare(strict_types=1);

namespace App\Enums;

enum ActionItemStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
