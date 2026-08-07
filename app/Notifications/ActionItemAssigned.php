<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ActionItem;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ActionItemAssigned extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly ActionItem $actionItem)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('You have been given an action: :title', [
            'title' => $this->actionItem->title,
        ]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            $this->actionItem->description ?? __('No further detail was recorded.'),
            $this->actionItem->due_on === null
                ? __('No due date was set.')
                : __('It is due by :date.', [
                    'date' => $this->actionItem->due_on->toFormattedDateString(),
                ]),
        ];
    }

    public function actionUrl(): string
    {
        return route('action-item.index');
    }

    public function actionLabel(): string
    {
        return __('View your actions');
    }
}
