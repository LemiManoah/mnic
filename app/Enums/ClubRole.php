<?php

declare(strict_types=1);

namespace App\Enums;

enum ClubRole: string
{
    case Member = 'member';
    case InterimChairperson = 'interim-chairperson';
    case Secretary = 'secretary';
    case Treasurer = 'treasurer';
    case FinancialVerifier = 'financial-verifier';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::InterimChairperson => 'Interim Chairperson',
            self::Secretary => 'Secretary',
            self::Treasurer => 'Treasurer',
            self::FinancialVerifier => 'Financial Verifier',
            self::Administrator => 'Administrator',
        };
    }
}
