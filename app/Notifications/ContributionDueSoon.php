<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MemberObligation;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * A nudge before the grace period closes, rather than a chase after it has.
 */
final class ContributionDueSoon extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly MemberObligation $obligation)
    {
        //
    }

    public function subjectLine(): string
    {
        return __(':period is due soon', [
            'period' => $this->obligation->contributionPeriod?->label() ?? '',
        ]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('You still owe :amount for :period.', [
                'amount' => number_format($this->obligation->outstanding()).' UGX',
                'period' => $this->obligation->contributionPeriod?->label() ?? '',
            ]),
            __('The grace period closes on :date, after which it counts as arrears.', [
                'date' => $this->obligation->contributionPeriod?->grace_ends_on->toFormattedDateString() ?? '',
            ]),
            __('If you have already paid, record it so an officer can verify it.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('payment.index');
    }

    public function actionLabel(): string
    {
        return __('Record a payment');
    }
}
