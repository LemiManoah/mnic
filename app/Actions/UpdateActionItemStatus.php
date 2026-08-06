<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

final readonly class UpdateActionItemStatus
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        ActionItem $actionItem,
        ActionItemStatus $status,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): ActionItem {
        return DB::transaction(function () use ($actionItem, $status, $actor, $ipAddress): ActionItem {
            $before = $actionItem->toArray();

            $actionItem->update([
                'status' => $status,
                'completed_at' => $status === ActionItemStatus::Completed ? now() : null,
            ]);

            $this->recordAuditEvent->handle(
                'action_item.status_changed',
                $actionItem,
                $actor,
                $before,
                $actionItem->toArray(),
                $ipAddress,
            );

            return $actionItem;
        });
    }
}
