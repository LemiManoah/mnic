<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContributionPeriod;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ContributionPeriodOpened extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly ContributionPeriod $period)
    {
        //
    }

    public function subjectLine(): string
    {
        return __(':period is open for contributions', [
            'period' => $this->period->label(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __(':amount is due by :due, with a grace period to :grace.', [
                'amount' => number_format($this->period->amount).' UGX',
                'due' => $this->period->due_date->toFormattedDateString(),
                'grace' => $this->period->grace_ends_on->toFormattedDateString(),
            ]),
        ];
    }

    public function actionUrl(): string
    {
        return route('contribution-period.show', $this->period);
    }

    public function actionLabel(): string
    {
        return __('View the month');
    }
}
