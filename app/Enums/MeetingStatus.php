<?php

declare(strict_types=1);

namespace App\Enums;

enum MeetingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }
}
