<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Member;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectReconciliation
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * Sends a submitted reconciliation back to the treasurer for correction.
     */
    public function handle(
        Reconciliation $reconciliation,
        Member $reviewer,
        string $reason,
        ?string $ipAddress = null,
    ): Reconciliation {
        throw_if($reconciliation->prepared_by_member_id === $reviewer->id, InvalidArgumentException::class, 'A reconciliation cannot be reviewed by the member who prepared it.');

        throw_if($reconciliation->status !== ReconciliationStatus::Submitted, InvalidArgumentException::class, 'Only a submitted reconciliation can be rejected.');

        return DB::transaction(function () use ($reconciliation, $reviewer, $reason, $ipAddress): Reconciliation {
            $before = $reconciliation->toArray();

            $reconciliation->update([
                'status' => ReconciliationStatus::Rejected,
                'rejection_reason' => $reason,
                'confirmed_by_member_id' => $reviewer->id,
            ]);

            $this->recordAuditEvent->handle(
                'reconciliation.rejected',
                $reconciliation,
                $reviewer,
                $before,
                $reconciliation->toArray(),
                $ipAddress,
            );

            return $reconciliation;
        });
    }
}
