<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MemberObligation;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class ObligationOverdue extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly MemberObligation $obligation)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('You are behind on :period', [
            'period' => $this->obligation->contributionPeriod->label(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('The grace period for :period closed on :date.', [
                'period' => $this->obligation->contributionPeriod->label(),
                'date' => $this->obligation->contributionPeriod->grace_ends_on->toFormattedDateString(),
            ]),
            __('You still owe :amount for that month.', [
                'amount' => number_format($this->obligation->outstanding()).' UGX',
            ]),
            __('If you have already paid, record it in the app so an officer can verify it.'),
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
