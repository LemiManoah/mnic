<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Apologies = 'apologies';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Apologies => 'Apologies',
            self::Absent => 'Absent',
        };
    }
}
