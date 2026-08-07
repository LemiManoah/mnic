<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollEligibleVoter;
use App\Models\PositionPollVote;
use App\Models\User;
use App\Policies\Concerns\EnforcesBusinessRules;

/**
 * Carries EnforcesBusinessRules because most of these answers depend on the
 * poll's state — is it still a draft, has this member already voted — and those
 * answers must not change for an administrator.
 */
final class PositionPollPolicy implements EnforcesBusinessRules
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
        return $user->can(Permission::PositionsManage->value);
    }

    /**
     * The ballot is fixed once voting opens, so nominations close with the draft.
     */
    public function nominate(User $user, PositionPoll $poll): bool
    {
        return $poll->status === PositionPollStatus::Draft
            && $user->can(Permission::PositionsManage->value);
    }

    public function openVoting(User $user, PositionPoll $poll): bool
    {
        return $poll->status === PositionPollStatus::Draft
            && $poll->candidates()->exists()
            && $user->can(Permission::PositionsManage->value);
    }

    public function closeVoting(User $user, PositionPoll $poll): bool
    {
        return $poll->status === PositionPollStatus::Open
            && $user->can(Permission::PositionsManage->value);
    }

    /**
     * One member, one vote — and only members captured in the eligibility
     * snapshot taken when voting opened.
     *
     * Deliberately not a permission: eligibility is decided by the frozen
     * electorate, so no role and no administrator bypass can add a voter to a
     * ballot that has already opened. CastPositionPollVote re-checks the same
     * rules.
     */
    public function vote(User $user, PositionPoll $poll): bool
    {
        if ($poll->status !== PositionPollStatus::Open) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        if ($member === null) {
            return false;
        }

        $isEligible = PositionPollEligibleVoter::query()
            ->where('position_poll_id', $poll->id)
            ->where('member_id', $member->id)
            ->exists();

        $hasVoted = PositionPollVote::query()
            ->where('position_poll_id', $poll->id)
            ->where('member_id', $member->id)
            ->exists();

        return $isEligible && ! $hasVoted;
    }
}
