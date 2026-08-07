<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionPoll;
use App\Models\PositionPollEligibleVoter;
use App\Models\Setting;
use App\Notifications\PositionPollVotingOpened;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final readonly class OpenPositionPollVoting
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(
        PositionPoll $poll,
        string $closesAt,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): PositionPoll {
        throw_if($poll->status !== PositionPollStatus::Draft, InvalidArgumentException::class, 'Only a draft poll can be opened for voting.');

        throw_if($poll->candidates()->count() === 0, InvalidArgumentException::class, 'A poll needs at least one candidate before voting can open.');

        $quorumPercent = $this->settingValue('quorum_percent');

        $poll = DB::transaction(function () use ($poll, $closesAt, $quorumPercent, $actor, $ipAddress): PositionPoll {
            $before = $poll->toArray();

            // Freeze the electorate: everyone active right now, and nobody else,
            // may vote in this election for the rest of its life.
            $eligible = Member::query()
                ->where('status', MemberStatus::Active->value)
                ->get();

            foreach ($eligible as $member) {
                PositionPollEligibleVoter::query()->create([
                    'position_poll_id' => $poll->id,
                    'member_id' => $member->id,
                ]);
            }

            $eligibleCount = $eligible->count();

            $poll->update([
                'status' => PositionPollStatus::Open,
                'opened_at' => now(),
                'closes_at' => $closesAt,
                'eligible_voter_count' => $eligibleCount,
                // Snapshotted so amending the club settings later cannot
                // retroactively change this election's outcome.
                'quorum_required' => (int) ceil($eligibleCount * $quorumPercent / 100),
            ]);

            $this->recordAuditEvent->handle(
                'position_poll.voting_opened',
                $poll,
                $actor,
                $before,
                $poll->toArray(),
                $ipAddress,
            );

            return $poll;
        });

        // The frozen roll, not the active roll — same people at this instant,
        // but only the snapshot stays true if somebody joins or leaves while
        // voting is open.
        $this->notifyMembers->handle(
            $poll->eligibleVoters()
                ->with('member.user')
                ->get()
                ->map(fn (PositionPollEligibleVoter $eligible) => $eligible->member)
                ->filter()
                ->values(),
            new PositionPollVotingOpened($poll),
        );

        return $poll;
    }

    private function settingValue(string $key): int
    {
        $version = Setting::query()->where('key', $key)->first()?->currentVersion();

        throw_if($version === null, RuntimeException::class, sprintf('Missing club setting [%s].', $key));

        return (int) $version->value;
    }
}
