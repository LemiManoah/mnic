<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\ProposalStatus;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\User;
use App\Models\Vote;
use App\Policies\Concerns\EnforcesBusinessRules;

final class ProposalPolicy implements EnforcesBusinessRules
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
        return $user->can(Permission::ProposalsCreate->value);
    }

    public function manageVoting(User $user): bool
    {
        return $user->can(Permission::ProposalsManageVoting->value);
    }

    /**
     * A proposal can only be pulled before it reaches a result. Once it has
     * passed or been rejected the membership has spoken, and the way to undo
     * that is another proposal, not a withdrawal.
     *
     * The status half is a state question, so the administrator bypass does
     * not apply — this policy carries EnforcesBusinessRules.
     */
    public function withdraw(User $user, Proposal $proposal): bool
    {
        return in_array($proposal->status, [ProposalStatus::Draft, ProposalStatus::Open], true)
            && $user->can(Permission::ProposalsManageVoting->value);
    }

    /**
     * One member, one vote — and only members captured in the eligibility
     * snapshot taken when voting opened.
     *
     * Deliberately not a permission: eligibility is decided by the frozen
     * electorate, so no role and no administrator bypass can add a voter to a
     * ballot that has already opened. CastVote re-checks the same rules.
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
