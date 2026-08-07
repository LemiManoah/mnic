<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Services\ClubCashPosition;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SubmitReconciliation
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private ClubCashPosition $cashPosition,
    ) {
        //
    }

    public function handle(Reconciliation $reconciliation, ?Member $actor = null, ?string $ipAddress = null): Reconciliation
    {
        throw_unless($reconciliation->status->isEditable(), InvalidArgumentException::class, 'Only a draft or rejected reconciliation can be submitted.');

        return DB::transaction(function () use ($reconciliation, $actor, $ipAddress): Reconciliation {
            $before = $reconciliation->toArray();

            $period = $reconciliation->contributionPeriod()->firstOrFail();

            // Expected = opening + verified inflows − settled outflows for the
            // month. The difference against the external statement is what the
            // club must then explain.
            $expected = $reconciliation->opening_balance
                + $this->cashPosition->inflowsForPeriod($period)
                - $this->cashPosition->outflowsForPeriod($period);

            $reconciliation->update([
                'expected_closing_balance' => max($expected, 0),
                'difference' => $reconciliation->statement_closing_balance - $expected,
                'status' => ReconciliationStatus::Submitted,
                'prepared_by_member_id' => $actor?->id,
                'submitted_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'reconciliation.submitted',
                $reconciliation,
                $actor,
                $before,
                $reconciliation->toArray(),
                $ipAddress,
            );

            return $reconciliation;
        });
    }
}
