<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PositionPoll;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PositionPollVotingOpened extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly PositionPoll $poll)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Election open: :title', ['title' => $this->poll->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('You are on the roll for this election.'),
            $this->poll->closes_at === null
                ? __('No closing time has been set.')
                : __('Voting closes :when.', [
                    'when' => $this->poll->closes_at->toDayDateTimeString(),
                ]),
            __('Read the candidates before you choose — you vote once, and it cannot be changed.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('position-poll.show', $this->poll);
    }

    public function actionLabel(): string
    {
        return __('See the candidates');
    }
}
