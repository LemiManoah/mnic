<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContributionPeriodStatus;
use App\Enums\ReconciliationStatus;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Notifications\MonthClosed;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Locks a confirmed reconciliation and closes its contribution period.
 *
 * After this, the month accepts controlled adjustments only — a locked
 * reconciliation can never be edited directly.
 */
final readonly class CloseMonth
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(Reconciliation $reconciliation, ?Member $actor = null, ?string $ipAddress = null): Reconciliation
    {
        throw_if($reconciliation->status !== ReconciliationStatus::Confirmed, InvalidArgumentException::class, 'Only a confirmed reconciliation can be locked.');

        $reconciliation = DB::transaction(function () use ($reconciliation, $actor, $ipAddress): Reconciliation {
            $before = $reconciliation->toArray();

            $reconciliation->update([
                'status' => ReconciliationStatus::Locked,
                'locked_at' => now(),
            ]);

            $reconciliation->contributionPeriod()->firstOrFail()->update([
                'status' => ContributionPeriodStatus::Closed,
            ]);

            $this->recordAuditEvent->handle(
                'reconciliation.locked',
                $reconciliation,
                $actor,
                $before,
                $reconciliation->toArray(),
                $ipAddress,
            );

            return $reconciliation;
        });

        // Closing is the moment a month's figures become final, so it is the
        // event worth announcing. The report itself has no publishing step —
        // it is readable all along.
        $period = $reconciliation->contributionPeriod;

        if ($period !== null) {
            $this->notifyMembers->toActiveMembers(new MonthClosed($period));
        }

        return $reconciliation;
    }
}
