<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Proposal;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent only to members on the frozen electorate who have not voted yet — a
 * reminder aimed at people who have already voted is noise, and telling them
 * apart is exactly what the eligibility snapshot is for.
 */
final class ProposalVotingClosing extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Proposal $proposal)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Last chance to vote: :title', ['title' => $this->proposal->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('Voting closes :when and you have not voted yet.', [
                'when' => $this->proposal->closes_at?->toDayDateTimeString() ?? __('shortly'),
            ]),
            __('If quorum is not reached the proposal fails regardless of how the votes fall.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('proposal.show', $this->proposal);
    }

    public function actionLabel(): string
    {
        return __('Vote now');
    }
}
