<?php

declare(strict_types=1);

namespace App\Enums;

enum ExternalAccountType: string
{
    case Bank = 'bank';
    case MobileMoney = 'mobile_money';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank account',
            self::MobileMoney => 'Mobile Money',
            self::Cash => 'Cash box',
        };
    }
}
