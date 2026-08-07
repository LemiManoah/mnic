<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdjustmentStatus;
use App\Enums\ContributionPeriodStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RequestPeriodAdjustment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * Raise a correction against a month that has already been closed.
     *
     * Only closed months come through here. An open month needs no adjustment
     * mechanism — the figures can still be fixed at source, and routing an
     * ordinary correction through a two-signature process would teach people to
     * treat the process as a formality.
     */
    public function handle(
        ContributionPeriod $period,
        int $amount,
        string $reason,
        Member $requester,
        ?string $ipAddress = null,
    ): PeriodAdjustment {
        throw_if(
            $period->status !== ContributionPeriodStatus::Closed,
            InvalidArgumentException::class,
            'Only a closed month needs an adjustment; correct an open month directly.',
        );

        throw_if($amount === 0, InvalidArgumentException::class, 'An adjustment of zero corrects nothing.');

        return DB::transaction(function () use ($period, $amount, $reason, $requester, $ipAddress): PeriodAdjustment {
            $adjustment = PeriodAdjustment::query()->create([
                'contribution_period_id' => $period->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => AdjustmentStatus::Pending,
                'requested_by_member_id' => $requester->id,
                'requested_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'period_adjustment.requested',
                $adjustment,
                $requester,
                null,
                $adjustment->toArray(),
                $ipAddress,
            );

            return $adjustment;
        });
    }
}
