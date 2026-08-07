<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\NotifyMembers;
use App\Enums\ActionItemStatus;
use App\Enums\ObligationStatus;
use App\Enums\PositionPollStatus;
use App\Enums\ProposalStatus;
use App\Models\ActionItem;
use App\Models\MemberObligation;
use App\Models\PositionPoll;
use App\Models\PositionPollEligibleVoter;
use App\Models\PositionPollVote;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;
use App\Notifications\ActionItemOverdue;
use App\Notifications\ContributionDueSoon;
use App\Notifications\ObligationOverdue;
use App\Notifications\PositionPollVotingClosing;
use App\Notifications\ProposalVotingClosing;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * The daily chase.
 *
 * Overdue is derived rather than stored, so this command never writes a status
 * — it reads what is already true and tells the people it affects. That means
 * it is safe to run twice in a day, and safe to miss a day.
 */
#[Description('Notify members of overdue contributions, overdue actions and votes about to close')]
#[Signature('club:sweep-overdue')]
final class SweepOverdueCommand extends Command
{
    public function handle(NotifyMembers $notify): int
    {
        $this->notifyDueSoon($notify);
        $this->notifyOverdueObligations($notify);
        $this->notifyOverdueActionItems($notify);
        $this->notifyClosingVotes($notify);
        $this->notifyClosingPolls($notify);

        return self::SUCCESS;
    }

    /**
     * A nudge in the three days before the grace period closes.
     *
     * Deliberately narrow: reminding somebody every day from the 1st is how a
     * reminder becomes background noise.
     */
    private function notifyDueSoon(NotifyMembers $notify): void
    {
        $dueSoon = MemberObligation::query()
            ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value])
            ->whereHas('contributionPeriod', fn (Builder $period): Builder => $period
                ->whereDate('grace_ends_on', '>=', today())
                ->whereDate('grace_ends_on', '<=', today()->addDays(3)))
            ->with(['member.user', 'contributionPeriod'])
            ->get();

        foreach ($dueSoon as $obligation) {
            if ($obligation->member === null) {
                continue;
            }

            $notify->handle([$obligation->member], new ContributionDueSoon($obligation));
        }

        $this->components->info(sprintf('Due within three days: %d', $dueSoon->count()));
    }

    private function notifyOverdueObligations(NotifyMembers $notify): void
    {
        $overdue = MemberObligation::overdueQuery()
            ->with(['member.user', 'contributionPeriod'])
            ->get();

        foreach ($overdue as $obligation) {
            if ($obligation->member === null) {
                continue;
            }

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

    /**
     * The same courtesy for elections as for proposals.
     */
    private function notifyClosingPolls(NotifyMembers $notify): void
    {
        $closing = PositionPoll::query()
            ->where('status', PositionPollStatus::Open->value)
            ->whereNotNull('closes_at')
            ->whereBetween('closes_at', [now(), now()->addDay()])
            ->get();

        foreach ($closing as $poll) {
            $voted = PositionPollVote::query()
                ->where('position_poll_id', $poll->id)
                ->pluck('member_id');

            $outstanding = PositionPollEligibleVoter::query()
                ->where('position_poll_id', $poll->id)
                ->whereNotIn('member_id', $voted)
                ->with('member.user')
                ->get()
                ->map(fn (PositionPollEligibleVoter $eligible) => $eligible->member)
                ->filter()
                ->values();

            $notify->handle($outstanding, new PositionPollVotingClosing($poll));
        }

        $this->components->info(sprintf('Elections closing within a day: %d', $closing->count()));
    }
}
