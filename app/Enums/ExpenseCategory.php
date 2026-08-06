<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseCategory: string
{
    case BankCharges = 'bank_charges';
    case Statutory = 'statutory';
    case Meeting = 'meeting';
    case Welfare = 'welfare';
    case Investment = 'investment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankCharges => 'Bank charges',
            self::Statutory => 'Statutory / registration',
            self::Meeting => 'Meeting costs',
            self::Welfare => 'Welfare',
            self::Investment => 'Investment',
            self::Other => 'Other',
        };
    }
}
