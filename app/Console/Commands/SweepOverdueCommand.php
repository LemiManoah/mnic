<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\NotifyMembers;
use App\Enums\ActionItemStatus;
use App\Enums\ProposalStatus;
use App\Models\ActionItem;
use App\Models\MemberObligation;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;
use App\Notifications\ActionItemOverdue;
use App\Notifications\ObligationOverdue;
use App\Notifications\ProposalVotingClosing;
use Illuminate\Console\Command;

/**
 * The daily chase.
 *
 * Overdue is derived rather than stored, so this command never writes a status
 * — it reads what is already true and tells the people it affects. That means
 * it is safe to run twice in a day, and safe to miss a day.
 */
final class SweepOverdueCommand extends Command
{
    protected $signature = 'club:sweep-overdue';

    protected $description = 'Notify members of overdue contributions, overdue actions and votes about to close';

    public function handle(NotifyMembers $notify): int
    {
        $this->notifyOverdueObligations($notify);
        $this->notifyOverdueActionItems($notify);
        $this->notifyClosingVotes($notify);

        return self::SUCCESS;
    }

    private function notifyOverdueObligations(NotifyMembers $notify): void
    {
        $overdue = MemberObligation::query()
            ->overdue()
            ->with(['member.user', 'contributionPeriod'])
            ->get();

        foreach ($overdue as $obligation) {
            $notify->handle([$obligation->member], new ObligationOverdue($obligation));
        }

        $this->components->info(sprintf('Overdue contributions: %d', $overdue->count()));
    }

    private function notifyOverdueActionItems(NotifyMembers $notify): void
    {
        $overdue = ActionItem::query()
            ->whereIn('status', [ActionItemStatus::Open->value, ActionItemStatus::InProgress->value])
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', today())
            ->with('ownerMember.user')
            ->get();

        foreach ($overdue as $item) {
            if ($item->ownerMember === null) {
                continue;
            }

            $notify->handle([$item->ownerMember], new ActionItemOverdue($item));
        }

        $this->components->info(sprintf('Overdue actions: %d', $overdue->count()));
    }

    /**
     * Reminds only the members who have not voted yet. Nagging somebody who
     * already voted is how people learn to ignore the emails.
     */
    private function notifyClosingVotes(NotifyMembers $notify): void
    {
        $closing = Proposal::query()
            ->where('status', ProposalStatus::Open->value)
            ->whereNotNull('closes_at')
            ->whereBetween('closes_at', [now(), now()->addDay()])
            ->get();

        foreach ($closing as $proposal) {
            $voted = Vote::query()
                ->where('proposal_id', $proposal->id)
                ->pluck('member_id');

            $outstanding = ProposalEligibleVoter::query()
                ->where('proposal_id', $proposal->id)
                ->whereNotIn('member_id', $voted)
                ->with('member.user')
                ->get()
                ->map(fn (ProposalEligibleVoter $eligible) => $eligible->member)
                ->filter()
                ->values();

            $notify->handle($outstanding, new ProposalVotingClosing($proposal));
        }

        $this->components->info(sprintf('Votes closing within a day: %d', $closing->count()));
    }
}
