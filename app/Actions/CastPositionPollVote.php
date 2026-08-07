<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use App\Models\PositionPollEligibleVoter;
use App\Models\PositionPollVote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CastPositionPollVote
{
    public function handle(
        PositionPoll $poll,
        Member $member,
        PositionPollCandidate $candidate,
    ): PositionPollVote {
        throw_if($poll->status !== PositionPollStatus::Open, InvalidArgumentException::class, 'Voting is not open on this poll.');

        throw_if($poll->closes_at !== null && $poll->closes_at->isPast(), InvalidArgumentException::class, 'Voting on this poll has closed.');

        throw_if($candidate->position_poll_id !== $poll->id, InvalidArgumentException::class, 'That candidate is not standing in this poll.');

        // Eligibility comes from the snapshot taken when voting opened, not
        // from the member's status today.
        $isEligible = PositionPollEligibleVoter::query()
            ->where('position_poll_id', $poll->id)
            ->where('member_id', $member->id)
            ->exists();

        throw_unless($isEligible, InvalidArgumentException::class, 'This member is not eligible to vote in this poll.');

        $hasVoted = PositionPollVote::query()
            ->where('position_poll_id', $poll->id)
            ->where('member_id', $member->id)
            ->exists();

        throw_if($hasVoted, InvalidArgumentException::class, 'This member has already voted in this poll.');

        return DB::transaction(fn (): PositionPollVote => PositionPollVote::query()->create([
            'position_poll_id' => $poll->id,
            'member_id' => $member->id,
            'position_poll_candidate_id' => $candidate->id,
            'cast_at' => now(),
        ]));
    }
}
