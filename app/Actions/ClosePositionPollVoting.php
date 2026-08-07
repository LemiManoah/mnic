<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PositionPollStatus;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\PositionPoll;
use App\Models\PositionPollCandidate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ClosePositionPollVoting
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private TransferPosition $transferPosition,
    ) {
        //
    }

    public function handle(PositionPoll $poll, ?Member $actor = null, ?string $ipAddress = null): PositionPoll
    {
        throw_if($poll->status !== PositionPollStatus::Open, InvalidArgumentException::class, 'Only an open poll can be closed.');

        return DB::transaction(function () use ($poll, $actor, $ipAddress): PositionPoll {
            $before = $poll->toArray();

            /** @var array<string, int> $tally */
            $tally = [];

            foreach ($poll->votes()->get() as $vote) {
                $tally[$vote->position_poll_candidate_id] =
                    ($tally[$vote->position_poll_candidate_id] ?? 0) + 1;
            }

            $turnout = array_sum($tally);
            $quorumMet = $turnout >= $poll->quorum_required;

            $highest = $tally === [] ? 0 : max($tally);
            $leaders = array_keys($tally, $highest, true);
            $tied = count($leaders) > 1;

            $winner = ($quorumMet && ! $tied && $leaders !== [])
                ? PositionPollCandidate::query()->find($leaders[0])
                : null;

            $poll->update([
                'status' => $winner === null ? PositionPollStatus::Failed : PositionPollStatus::Decided,
                'closed_at' => now(),
                'winning_candidate_id' => $winner?->id,
                'outcome_note' => $this->outcomeNote($poll, $turnout, $quorumMet, $tied, $highest, $winner),
            ]);

            if ($winner !== null) {
                $this->transferOffice($poll, $winner, $actor, $ipAddress);
            }

            $this->recordAuditEvent->handle(
                'position_poll.voting_closed',
                $poll,
                $actor,
                $before,
                $poll->toArray(),
                $ipAddress,
            );

            return $poll;
        });
    }

    /**
     * Hand the office to the winner, unless they already hold it.
     *
     * An incumbent standing for re-election wins the same seat they are sitting
     * in; transferring it to themselves would be rejected as a no-op, so the
     * holding is simply left in place.
     */
    private function transferOffice(
        PositionPoll $poll,
        PositionPollCandidate $winner,
        ?Member $actor,
        ?string $ipAddress,
    ): void {
        $incumbent = PositionHolding::query()
            ->where('position', $poll->position->value)
            ->whereNull('held_to')
            ->first();

        if ($incumbent?->member_id === $winner->member_id) {
            return;
        }

        $this->transferPosition->handle(
            $poll->position,
            $winner->member()->firstOrFail(),
            now()->toDateString(),
            $actor,
            $poll,
            sprintf('Won the election: %s', $poll->title),
            $ipAddress,
        );
    }

    private function outcomeNote(
        PositionPoll $poll,
        int $turnout,
        bool $quorumMet,
        bool $tied,
        int $highest,
        ?PositionPollCandidate $winner,
    ): string {
        if (! $quorumMet) {
            return sprintf(
                'Failed: turnout %d of %d eligible did not meet the quorum of %d. The office is unchanged.',
                $turnout,
                $poll->eligible_voter_count,
                $poll->quorum_required,
            );
        }

        if ($tied) {
            return sprintf(
                'Failed: tied on %d votes each from a turnout of %d. The chairperson must call a fresh poll.',
                $highest,
                $turnout,
            );
        }

        return sprintf(
            '%s won with %d of %d votes cast (quorum %d met, %d eligible).',
            $winner?->member()->first()->full_name ?? 'Unknown member',
            $highest,
            $turnout,
            $poll->quorum_required,
            $poll->eligible_voter_count,
        );
    }
}
