<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProposalStatus;
use App\Models\Member;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class WithdrawProposal
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * Take a proposal off the table before it reaches a result.
     *
     * Votes already cast are deliberately left alone. A withdrawn proposal that
     * had votes on it is part of the club's record — deleting them would hide
     * that people had already committed a position before it was pulled.
     */
    public function handle(
        Proposal $proposal,
        string $reason,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Proposal {
        throw_unless(
            in_array($proposal->status, [ProposalStatus::Draft, ProposalStatus::Open], true),
            InvalidArgumentException::class,
            'Only a draft or open proposal can be withdrawn.',
        );

        return DB::transaction(function () use ($proposal, $reason, $actor, $ipAddress): Proposal {
            $before = $proposal->toArray();

            $proposal->update([
                'status' => ProposalStatus::Withdrawn,
                'closed_at' => now(),
                'withdrawal_reason' => $reason,
                'outcome_note' => sprintf('Withdrawn before a result was recorded. %s', $reason),
            ]);

            $this->recordAuditEvent->handle(
                'proposal.withdrawn',
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
