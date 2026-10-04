<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ObligationStatus;
use App\Models\Member;
use App\Models\MemberObligation;

final readonly class GetPaymentContributionDue
{
    /**
     * @return array{amount: int, period: string|null}
     */
    public function handle(Member $member): array
    {
        $member->loadMissing('obligations.contributionPeriod');

        $obligation = $member->obligations
            ->filter(fn (MemberObligation $obligation): bool => in_array($obligation->status, [ObligationStatus::Unpaid, ObligationStatus::PartiallyPaid], true) && $obligation->outstanding() > 0)
            ->sortBy(fn (MemberObligation $obligation): string => sprintf('%04d-%02d', $obligation->contributionPeriod->year ?? 0, $obligation->contributionPeriod->month ?? 0))
            ->first();

        return [
            'amount' => $obligation?->outstanding() ?? 0,
            'period' => $obligation?->contributionPeriod?->label(),
        ];
    }
}
