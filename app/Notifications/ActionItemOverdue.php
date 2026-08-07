<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ActionItem;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ActionItemOverdue extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly ActionItem $actionItem)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Overdue action: :title', ['title' => $this->actionItem->title]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('This was due on :date and is still open.', [
                'date' => $this->actionItem->due_on?->toFormattedDateString() ?? __('an earlier date'),
            ]),
            __('Update it in the app so the club can see where it stands.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('action-item.index');
    }

    public function actionLabel(): string
    {
        return __('Update the action');
    }
}
