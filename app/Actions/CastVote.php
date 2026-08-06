<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CastVote
{
    public function handle(
        Proposal $proposal,
        Member $member,
        VoteChoice $choice,
        bool $hasConflict = false,
        ?string $conflictNote = null,
    ): Vote {
        throw_if($proposal->status !== ProposalStatus::Open, InvalidArgumentException::class, 'Voting is not open on this proposal.');

        throw_if($proposal->closes_at !== null && $proposal->closes_at->isPast(), InvalidArgumentException::class, 'Voting on this proposal has closed.');

        // Eligibility comes from the snapshot taken when voting opened, not
        // from the member's status today.
        $isEligible = ProposalEligibleVoter::query()
            ->where('proposal_id', $proposal->id)
            ->where('member_id', $member->id)
            ->exists();

        throw_unless($isEligible, InvalidArgumentException::class, 'This member is not eligible to vote on this proposal.');

        $hasVoted = Vote::query()
            ->where('proposal_id', $proposal->id)
            ->where('member_id', $member->id)
            ->exists();

        throw_if($hasVoted, InvalidArgumentException::class, 'This member has already voted on this proposal.');

        return DB::transaction(fn (): Vote => Vote::query()->create([
            'proposal_id' => $proposal->id,
            'member_id' => $member->id,
            'choice' => $choice,
            'has_conflict' => $hasConflict,
            'conflict_note' => $conflictNote,
            'cast_at' => now(),
        ]));
    }
}
