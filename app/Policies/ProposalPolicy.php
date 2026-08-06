<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Enums\ProposalStatus;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\User;
use App\Models\Vote;

final class ProposalPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::InterimChairperson->value,
            ClubRole::Secretary->value,
            ClubRole::Administrator->value,
        ]);
    }

    public function manageVoting(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::InterimChairperson->value,
            ClubRole::Secretary->value,
            ClubRole::Administrator->value,
        ]);
    }

    /**
     * One member, one vote — and only members captured in the eligibility
     * snapshot taken when voting opened.
     */
    public function vote(User $user, Proposal $proposal): bool
    {
        if ($proposal->status !== ProposalStatus::Open) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        if ($member === null) {
            return false;
        }

        $isEligible = ProposalEligibleVoter::query()
            ->where('proposal_id', $proposal->id)
            ->where('member_id', $member->id)
            ->exists();

        $hasVoted = Vote::query()
            ->where('proposal_id', $proposal->id)
            ->where('member_id', $member->id)
            ->exists();

        return $isEligible && ! $hasVoted;
    }
}
