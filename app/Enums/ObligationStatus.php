<?php

declare(strict_types=1);

namespace App\Enums;

enum ObligationStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Waived => 'Waived',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether this obligation can still receive payment allocations.
     */
    public function isSettleable(): bool
    {
        return in_array($this, [self::Unpaid, self::PartiallyPaid], true);
    }
}
