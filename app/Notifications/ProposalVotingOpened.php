<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Proposal;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ProposalVotingOpened extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Proposal $proposal)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Voting is open: :title', ['title' => $this->proposal->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('You are on the roll for this vote.'),
            $this->proposal->closes_at === null
                ? __('No closing time has been set.')
                : __('Voting closes :when.', [
                    'when' => $this->proposal->closes_at->toDayDateTimeString(),
                ]),
            __('You may vote once, and it cannot be changed afterwards.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('proposal.show', $this->proposal);
    }

    public function actionLabel(): string
    {
        return __('Read it and vote');
    }
}
