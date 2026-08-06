<?php

declare(strict_types=1);

namespace App\Enums;

enum VoteChoice: string
{
    case For = 'for';
    case Against = 'against';
    case Abstain = 'abstain';

    public function label(): string
    {
        return match ($this) {
            self::For => 'For',
            self::Against => 'Against',
            self::Abstain => 'Abstain',
        };
    }
}
