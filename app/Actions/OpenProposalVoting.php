<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Enums\ProposalStatus;
use App\Models\Member;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Setting;
use App\Notifications\ProposalVotingOpened;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final readonly class OpenProposalVoting
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(
        Proposal $proposal,
        string $closesAt,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Proposal {
        throw_if($proposal->status !== ProposalStatus::Draft, InvalidArgumentException::class, 'Only a draft proposal can be opened for voting.');

        $quorumPercent = $this->settingValue('quorum_percent');
        $approvalPercent = $this->settingValue('approval_percent');

        $proposal = DB::transaction(function () use ($proposal, $closesAt, $quorumPercent, $approvalPercent, $actor, $ipAddress): Proposal {
            $before = $proposal->toArray();

            // Freeze the electorate: everyone active right now, and nobody else,
            // may vote on this proposal for the rest of its life.
            $eligible = Member::query()
                ->where('status', MemberStatus::Active->value)
                ->get();

            foreach ($eligible as $member) {
                ProposalEligibleVoter::query()->create([
                    'proposal_id' => $proposal->id,
                    'member_id' => $member->id,
                ]);
            }

            $eligibleCount = $eligible->count();

            $proposal->update([
                'status' => ProposalStatus::Open,
                'opened_at' => now(),
                'closes_at' => $closesAt,
                'eligible_voter_count' => $eligibleCount,
                // Thresholds are snapshotted too, so amending the club settings
                // later cannot retroactively change this vote's outcome.
                'quorum_required' => (int) ceil($eligibleCount * $quorumPercent / 100),
                'approval_percent' => $approvalPercent,
            ]);

            $this->recordAuditEvent->handle(
                'proposal.voting_opened',
                $proposal,
                $actor,
                $before,
                $proposal->toArray(),
                $ipAddress,
            );

            return $proposal;
        });

        // Told to the frozen electorate rather than the active roll: those are
        // the same people at this instant, but only the snapshot stays true if
        // somebody joins or leaves while voting is open.
        $this->notifyMembers->handle(
            $proposal->eligibleVoters()
                ->with('member.user')
                ->get()
                ->map(fn (ProposalEligibleVoter $eligible) => $eligible->member)
                ->filter()
                ->values(),
            new ProposalVotingOpened($proposal),
        );

        return $proposal;
    }

    private function settingValue(string $key): int
    {
        $version = Setting::query()->where('key', $key)->first()?->currentVersion();

        throw_if($version === null, RuntimeException::class, sprintf('Missing club setting [%s].', $key));

        return (int) $version->value;
    }
}
