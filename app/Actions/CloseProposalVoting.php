<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\Member;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CloseProposalVoting
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private TransferPosition $transferPosition,
    )
    {
        //
    }

    public function handle(Proposal $proposal, ?Member $actor = null, ?string $ipAddress = null): Proposal
    {
        throw_if($proposal->status !== ProposalStatus::Open, InvalidArgumentException::class, 'Only an open proposal can be closed.');

        return DB::transaction(function () use ($proposal, $actor, $ipAddress): Proposal {
            $before = $proposal->toArray();

            $for = $proposal->countChoice(VoteChoice::For);
            $against = $proposal->countChoice(VoteChoice::Against);
            $abstain = $proposal->countChoice(VoteChoice::Abstain);

            // Abstentions count towards quorum (the member turned up) but are
            // excluded from the approval calculation.
            $turnout = $for + $against + $abstain;
            $decisive = $for + $against;

            $quorumMet = $turnout >= $proposal->quorum_required;
            $approvalMet = $decisive > 0
                && ($for * 100) >= ($decisive * $proposal->approval_percent);

            $passed = $quorumMet && $approvalMet;

            $proposal->update([
                'status' => $passed ? ProposalStatus::Passed : ProposalStatus::Rejected,
                'closed_at' => now(),
                'outcome_note' => sprintf(
                    'For %d, against %d, abstain %d. Turnout %d of %d eligible (quorum %d, %s). Approval threshold %d%%.',
                    $for,
                    $against,
                    $abstain,
                    $turnout,
                    $proposal->eligible_voter_count,
                    $proposal->quorum_required,
                    $quorumMet ? 'met' : 'not met',
                    $proposal->approval_percent,
                ),
            ]);

            if ($passed && $proposal->isElection()) {
                $proposal->loadMissing('electionMember');

                $this->transferPosition->handle(
                    $proposal->election_position,
                    $proposal->electionMember,
                    now()->toDateString(),
                    $actor,
                    $proposal,
                    sprintf('Passed proposal: %s', $proposal->title),
                    $ipAddress,
                );
            }

            $this->recordAuditEvent->handle(
                'proposal.voting_closed',
                $proposal,
                $actor,
                $before,
                $proposal->toArray(),
                $ipAddress,
            );

            return $proposal;
        });
    }
}
