<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Bank Transfer',
            self::Cash => 'Cash',
        };
    }
}
