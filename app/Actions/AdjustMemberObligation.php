<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ObligationStatus;
use App\Models\Member;
use App\Models\MemberObligation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class AdjustMemberObligation
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        MemberObligation $obligation,
        ObligationStatus $status,
        string $reason,
        Member $actor,
        ?string $ipAddress = null,
    ): MemberObligation {
        throw_unless(
            in_array($status, [ObligationStatus::Waived, ObligationStatus::Cancelled], true),
            InvalidArgumentException::class,
            'Only waived or cancelled adjustments are supported.',
        );

        return DB::transaction(function () use ($obligation, $status, $reason, $actor, $ipAddress): MemberObligation {
            $obligation = MemberObligation::query()
                ->lockForUpdate()
                ->findOrFail($obligation->id);

            throw_unless($obligation->status->isSettleable(), InvalidArgumentException::class, 'Only unpaid or partially paid obligations can be adjusted.');
            throw_if($obligation->amount_paid > 0, InvalidArgumentException::class, 'Reverse payments before waiving or cancelling an obligation with payments.');
            throw_if($obligation->allocations()->exists(), InvalidArgumentException::class, 'Reverse payment allocations before adjusting this obligation.');

            $before = $obligation->toArray();

            $obligation->update([
                'status' => $status,
                'amount_paid' => 0,
                'adjusted_by_member_id' => $actor->id,
                'adjusted_at' => now(),
                'adjustment_reason' => $reason,
            ]);

            $this->recordAuditEvent->handle(
                $status === ObligationStatus::Waived
                    ? 'obligation.waived'
                    : 'obligation.cancelled',
                $obligation,
                $actor,
                $before,
                $obligation->toArray(),
                $ipAddress,
            );

            return $obligation;
        });
    }
}
