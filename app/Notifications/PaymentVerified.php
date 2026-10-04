<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PaymentVerified extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Payment $payment)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Your payment has been verified');
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('Your payment of :amount (reference :reference) has been verified by a second officer.', [
                'amount' => number_format($this->payment->amount).' UGX',
                'reference' => $this->payment->reference,
            ]),
            __($this->payment->contribution_period_id === null
                ? 'Contributions and advance: :amount UGX. Applied to your oldest unpaid months first; any remainder is held as advance credit.'
                : 'Contributions and advance: :amount UGX. Applied to your selected month and later open months; any remainder is held as advance credit.', [
                    'amount' => number_format($this->payment->contributionAmount()),
                ]),
            __('Withdrawal fee contribution collected: :amount UGX. This does not count towards your contribution balance.', [
                'amount' => number_format($this->payment->withdrawal_fee_amount),
            ]),
        ];
    }

    public function actionUrl(): string
    {
        return route('payment.index');
    }

    public function actionLabel(): string
    {
        return __('View your payments');
    }
}
