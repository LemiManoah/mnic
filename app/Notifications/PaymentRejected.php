<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PaymentRejected extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Payment $payment)
    {
        //
    }

    public function subjectLine(): string
    {
        return __('Your payment could not be verified');
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        return [
            __('Your payment of :amount (reference :reference) was not accepted.', [
                'amount' => number_format($this->payment->amount).' UGX',
                'reference' => $this->payment->reference,
            ]),
            __('Reason given: :reason', [
                'reason' => $this->payment->rejection_reason ?? __('none recorded'),
            ]),
            __('It does not count towards your contributions. Please resubmit with the evidence the officer asked for.'),
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
