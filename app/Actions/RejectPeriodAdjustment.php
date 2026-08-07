<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdjustmentStatus;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectPeriodAdjustment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        PeriodAdjustment $adjustment,
        Member $approver,
        string $reason,
        ?string $ipAddress = null,
    ): PeriodAdjustment {
        throw_if(
            $adjustment->requested_by_member_id === $approver->id,
            InvalidArgumentException::class,
            'An adjustment cannot be reviewed by the member who requested it.',
        );

        throw_unless(
            $adjustment->isPending(),
            InvalidArgumentException::class,
            'Only a pending adjustment can be rejected.',
        );

        return DB::transaction(function () use ($adjustment, $approver, $reason, $ipAddress): PeriodAdjustment {
            $before = $adjustment->toArray();

            $adjustment->update([
                'status' => AdjustmentStatus::Rejected,
                'reviewed_by_member_id' => $approver->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->recordAuditEvent->handle(
                'period_adjustment.rejected',
                $adjustment,
                $approver,
                $before,
                $adjustment->toArray(),
                $ipAddress,
            );

            return $adjustment;
        });
    }
}
