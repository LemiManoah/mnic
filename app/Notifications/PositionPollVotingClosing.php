<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PositionPoll;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent only to members on the frozen roll who have not voted yet.
 */
final class PositionPollVotingClosing extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly PositionPoll $poll)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Last chance to vote: :title', ['title' => $this->poll->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('Voting closes :when and you have not voted yet.', [
                'when' => $this->poll->closes_at?->toDayDateTimeString() ?? __('shortly'),
            ]),
            __('If turnout falls short of quorum the seat stays as it is, whoever is ahead.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('position-poll.show', $this->poll);
    }

    public function actionLabel(): string
    {
        return __('Vote now');
    }
}
