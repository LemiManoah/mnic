<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordReconciliationItem
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * Documents part of the difference between the club's expected balance and
     * the external statement.
     */
    public function handle(
        Reconciliation $reconciliation,
        string $description,
        int $amount,
        ?Member $assignedTo = null,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): ReconciliationItem {
        throw_if($reconciliation->isLocked(), InvalidArgumentException::class, 'A locked reconciliation cannot be changed.');

        return DB::transaction(function () use ($reconciliation, $description, $amount, $assignedTo, $actor, $ipAddress): ReconciliationItem {
            $item = ReconciliationItem::query()->create([
                'reconciliation_id' => $reconciliation->id,
                'description' => $description,
                'amount' => $amount,
                'is_resolved' => false,
                'assigned_to_member_id' => $assignedTo?->id,
            ]);

            $this->recordAuditEvent->handle(
                'reconciliation_item.recorded',
                $item,
                $actor,
                null,
                $item->toArray(),
                $ipAddress,
            );

            return $item;
        });
    }
}
