<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberStatus: string
{
    case Prospective = 'prospective';
    case Active = 'active';
    case Suspended = 'suspended';
    case Exited = 'exited';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Prospective => 'Prospective',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Exited => 'Exited',
            self::Removed => 'Removed',
        };
    }
}
