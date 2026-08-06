<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Paid => 'Paid',
            self::Verified => 'Verified',
        };
    }
}
