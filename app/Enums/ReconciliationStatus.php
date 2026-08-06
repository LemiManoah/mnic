<?php

declare(strict_types=1);

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Locked => 'Locked',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
