<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContributionPeriod;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent when a month is locked.
 *
 * The monthly report has no "publish" step — it is always readable — so there
 * is no publishing event to announce. Closing the month is the moment its
 * figures actually become final, which is the thing members care about.
 */
final class MonthClosed extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly ContributionPeriod $period)
    {
        //
    }

    public function subjectLine(): string
    {
        return __(':period is closed', ['period' => $this->period->label()]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('The month has been reconciled against the statement and locked, so its figures are now final.'),
            __('The report is worth a look — if anything in it looks wrong, say so, because correcting a closed month takes two officers.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('monthly-report.show', $this->period);
    }

    public function actionLabel(): string
    {
        return __('Read the report');
    }
}
