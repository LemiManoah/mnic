<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordExpensePayment
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(
        Expense $expense,
        string $externalAccountId,
        string $paymentReference,
        string $paidOn,
        ?Member $actor = null,
        ?string $ipAddress = null,
    ): Expense {
        throw_if($expense->status !== ExpenseStatus::Approved, InvalidArgumentException::class, 'Only an approved expense can be paid.');

        return DB::transaction(function () use ($expense, $externalAccountId, $paymentReference, $paidOn, $actor, $ipAddress): Expense {
            $before = $expense->toArray();

            $expense->update([
                'status' => ExpenseStatus::Paid,
                'external_account_id' => $externalAccountId,
                'payment_reference' => $paymentReference,
                'paid_on' => $paidOn,
            ]);

            $this->recordAuditEvent->handle(
                'expense.paid',
                $expense,
                $actor,
                $before,
                $expense->toArray(),
                $ipAddress,
            );

            return $expense;
        });
    }
}
