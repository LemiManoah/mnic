<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReconciliationStatus;
use App\Models\Member;
use App\Models\Reconciliation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ConfirmReconciliation
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Reconciliation $reconciliation, Member $confirmer, ?string $ipAddress = null): Reconciliation
    {
        // Maker-checker: the treasurer who prepared it cannot confirm it.
        throw_if($reconciliation->prepared_by_member_id === $confirmer->id, InvalidArgumentException::class, 'A reconciliation cannot be confirmed by the member who prepared it.');

        throw_if($reconciliation->status !== ReconciliationStatus::Submitted, InvalidArgumentException::class, 'Only a submitted reconciliation can be confirmed.');

        $unresolved = $reconciliation->items()->where('is_resolved', false)->exists();

        throw_if($unresolved, InvalidArgumentException::class, 'Every reconciliation item must be resolved before confirmation.');

        return DB::transaction(function () use ($reconciliation, $confirmer, $ipAddress): Reconciliation {
            $before = $reconciliation->toArray();

            $reconciliation->update([
                'status' => ReconciliationStatus::Confirmed,
                'confirmed_by_member_id' => $confirmer->id,
                'confirmed_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'reconciliation.confirmed',
                $reconciliation,
                $confirmer,
                $before,
                $reconciliation->toArray(),
                $ipAddress,
            );

            return $reconciliation;
        });
    }
}
