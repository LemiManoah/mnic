<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Models\ReconciliationItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ResolveReconciliationItem
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(ReconciliationItem $item, ?Member $actor = null, ?string $ipAddress = null): ReconciliationItem
    {
        $item->loadMissing('reconciliation');

        throw_if($item->reconciliation->isLocked(), InvalidArgumentException::class, 'A locked reconciliation cannot be changed.');

        return DB::transaction(function () use ($item, $actor, $ipAddress): ReconciliationItem {
            $before = $item->toArray();

            $item->update(['is_resolved' => true]);

            $this->recordAuditEvent->handle(
                'reconciliation_item.resolved',
                $item,
                $actor,
                $before,
                $item->toArray(),
                $ipAddress,
            );

            return $item;
        });
    }
}
