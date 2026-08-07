<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdjustmentStatus;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ApprovePeriodAdjustment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(PeriodAdjustment $adjustment, Member $approver, ?string $ipAddress = null): PeriodAdjustment
    {
        // Maker-checker, re-checked here as well as in the policy so it holds
        // for any caller. Correcting a signed-off month on your own say-so is
        // exactly what this whole mechanism exists to prevent.
        throw_if(
            $adjustment->requested_by_member_id === $approver->id,
            InvalidArgumentException::class,
            'An adjustment cannot be approved by the member who requested it.',
        );

        throw_unless(
            $adjustment->isPending(),
            InvalidArgumentException::class,
            'Only a pending adjustment can be approved.',
        );

        return DB::transaction(function () use ($adjustment, $approver, $ipAddress): PeriodAdjustment {
            $before = $adjustment->toArray();

            $adjustment->update([
                'status' => AdjustmentStatus::Approved,
                'reviewed_by_member_id' => $approver->id,
                'reviewed_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'period_adjustment.approved',
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
