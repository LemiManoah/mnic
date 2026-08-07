<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use App\Models\Minute;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class MinutesPublished extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(
        private readonly Meeting $meeting,
        private readonly Minute $minute,
    ) {
        //
    }

    public function subjectLine(): string
    {
        if ($this->minute->isCorrection()) {
            return __('Corrected minutes for :title', ['title' => $this->meeting->title]);
        }

        return __('Minutes published for :title', ['title' => $this->meeting->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        if ($this->minute->isCorrection()) {
            return [
                __('Version :version corrects the confirmed record.', [
                    'version' => $this->minute->version,
                ]),
                __('Reason given: :reason', [
                    'reason' => $this->minute->correction_reason ?? __('none recorded'),
                ]),
                __('The earlier version stays in the record. The correction is not official until it is confirmed.'),
            ];
        }

        return [
            __('Version :version is now available to read.', [
                'version' => $this->minute->version,
            ]),
            __('Raise anything you disagree with before the club confirms them.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('meeting.show', $this->meeting);
    }

    public function actionLabel(): string
    {
        return __('Read the minutes');
    }
}
