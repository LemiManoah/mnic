<?php

declare(strict_types=1);

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Passed = 'passed';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Passed => 'Passed',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
