<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Reversed => 'Reversed',
        };
    }
}
