<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The life cycle of an election for a single office.
 *
 * A poll that closes without a winner is Failed rather than Decided: the office
 * keeps its current holder and the club has to run another poll. That covers
 * both "too few members turned up" and "two candidates tied".
 */
enum PositionPollStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Decided = 'decided';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Decided => 'Decided',
            self::Failed => 'Failed',
        };
    }
}
