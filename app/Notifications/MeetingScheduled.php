<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class MeetingScheduled extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Meeting $meeting)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Meeting: :title', ['title' => $this->meeting->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __(':reference is set for :when.', [
                'reference' => $this->meeting->reference,
                'when' => $this->meeting->scheduled_for->toDayDateTimeString(),
            ]),
            $this->meeting->location === null
                ? __('The venue has not been confirmed yet.')
                : __('Venue: :location', ['location' => $this->meeting->location]),
        ];
    }

    public function actionUrl(): string
    {
        return route('meeting.show', $this->meeting);
    }

    public function actionLabel(): string
    {
        return __('See the agenda');
    }
}
