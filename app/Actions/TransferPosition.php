<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClubPosition;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class TransferPosition
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        ClubPosition $position,
        Member $successor,
        string $heldFrom,
        ?Member $actor = null,
        ?PositionPoll $poll = null,
        ?string $reason = null,
        ?string $ipAddress = null,
    ): PositionHolding {
        return DB::transaction(function () use ($position, $successor, $heldFrom, $actor, $poll, $reason, $ipAddress): PositionHolding {
            $current = PositionHolding::query()
                ->where('position', $position->value)
                ->whereNull('held_to')
                ->lockForUpdate()
                ->first();

            throw_if($current?->member_id === $successor->id, InvalidArgumentException::class, 'This member already holds that position.');

            $before = [
                'position' => $position->value,
                'from_member_id' => $current?->member_id,
                'to_member_id' => $successor->id,
            ];

            if ($current !== null) {
                $current->update(['held_to' => $heldFrom]);
                $current->member()->update(['position' => null]);
            }

            PositionHolding::query()
                ->where('member_id', $successor->id)
                ->where('position', '!=', $position->value)
                ->whereNull('held_to')
                ->lockForUpdate()
                ->get()
                ->each(fn (PositionHolding $holding): bool => $holding->update(['held_to' => $heldFrom]));

            $holding = PositionHolding::query()->create([
                'member_id' => $successor->id,
                'position' => $position,
                'held_from' => $heldFrom,
                'held_to' => null,
                'elected_via_position_poll_id' => $poll?->id,
                'appointed_by_member_id' => $actor?->id,
                'transfer_reason' => $reason,
            ]);

            $successor->update(['position' => $position]);

            $this->recordAuditEvent->handle(
                'position.transferred',
                $holding,
                $actor,
                $before,
                $holding->toArray(),
                $ipAddress,
            );

            return $holding;
        });
    }
}
