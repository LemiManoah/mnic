<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Notifications\Concerns\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Approved or rejected, told to whoever asked for the money.
 *
 * One notification rather than two: the requester's question is the same either
 * way — "what happened to my request?" — and splitting it would duplicate the
 * routing for no gain.
 */
final class ExpenseDecided extends Notification implements ShouldQueue
{
    use ClubNotification;
    use Queueable;

    public function __construct(private readonly Expense $expense)
    {
        //
    }

    public function subjectLine(): string
    {
        if ($this->expense->status === ExpenseStatus::Rejected) {
            return __('Expense declined: :reference', [
                'reference' => $this->expense->reference,
            ]);
        }

        return __('Expense approved: :reference', [
            'reference' => $this->expense->reference,
        ]);
    }

    /**
     * @return list<string>
     */
    public function bodyLines(): array
    {
        $amount = number_format($this->expense->amount).' UGX';

        if ($this->expense->status === ExpenseStatus::Rejected) {
            return [
                __('Your request for :amount towards ":purpose" was declined.', [
                    'amount' => $amount,
                    'purpose' => $this->expense->purpose,
                ]),
                __('Reason given: :reason', [
                    'reason' => $this->expense->rejection_reason ?? __('none recorded'),
                ]),
            ];
        }

        return [
            __('Your request for :amount towards ":purpose" was approved.', [
                'amount' => $amount,
                'purpose' => $this->expense->purpose,
            ]),
            __('The treasurer will record the payment once the money has actually moved.'),
        ];
    }

    public function actionUrl(): string
    {
        return route('expense.index');
    }

    public function actionLabel(): string
    {
        return __('View expenses');
    }
}
