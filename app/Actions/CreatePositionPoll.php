<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClubPosition;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreatePositionPoll
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        ClubPosition $position,
        string $title,
        ?string $description = null,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): PositionPoll {
        return DB::transaction(function () use ($position, $title, $description, $actor, $ipAddress): PositionPoll {
            // Two live races for the same office would produce two winners and
            // an unresolvable transfer.
            $contested = PositionPoll::query()
                ->where('position', $position->value)
                ->whereIn('status', [PositionPollStatus::Draft->value, PositionPollStatus::Open->value])
                ->lockForUpdate()
                ->exists();

            throw_if($contested, InvalidArgumentException::class, 'There is already a poll under way for this office.');

            $poll = PositionPoll::query()->create([
                'position' => $position,
                'title' => $title,
                'description' => $description,
                'status' => PositionPollStatus::Draft,
                'created_by_member_id' => $actor?->id,
            ]);

            $this->recordAuditEvent->handle(
                'position_poll.created',
                $poll,
                $actor,
                null,
                $poll->toArray(),
                $ipAddress,
            );

            return $poll;
        });
    }
}
