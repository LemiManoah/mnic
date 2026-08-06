<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

final readonly class CreateActionItem
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, ?Member $actor = null, ?string $ipAddress = null): ActionItem
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress): ActionItem {
            $actionItem = ActionItem::query()->create([
                ...$attributes,
                'status' => ActionItemStatus::Open,
                'created_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle(
                'action_item.created',
                $actionItem,
                $actor,
                null,
                $actionItem->toArray(),
                $ipAddress,
            );

            return $actionItem;
        });
    }
}
