<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class NominatePositionPollCandidate
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        PositionPoll $poll,
        Member $candidate,
        ?string $manifesto = null,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): PositionPollCandidate {
        // The ballot is fixed before voting starts, so nobody can join the race
        // after seeing how the early votes are going.
        throw_if($poll->status !== PositionPollStatus::Draft, InvalidArgumentException::class, 'Candidates can only be nominated while the poll is a draft.');

        throw_if($candidate->status !== MemberStatus::Active, InvalidArgumentException::class, 'Only an active member can stand for office.');

        return DB::transaction(function () use ($poll, $candidate, $manifesto, $actor, $ipAddress): PositionPollCandidate {
            $alreadyStanding = PositionPollCandidate::query()
                ->where('position_poll_id', $poll->id)
                ->where('member_id', $candidate->id)
                ->lockForUpdate()
                ->exists();

            throw_if($alreadyStanding, InvalidArgumentException::class, 'This member is already standing in this poll.');

            $nomination = PositionPollCandidate::query()->create([
                'position_poll_id' => $poll->id,
                'member_id' => $candidate->id,
                'nominated_by_member_id' => $actor?->id,
                'manifesto' => $manifesto,
            ]);

            $this->recordAuditEvent->handle(
                'position_poll.candidate_nominated',
                $nomination,
                $actor,
                null,
                $nomination->toArray(),
                $ipAddress,
            );

            return $nomination;
        });
    }
}
