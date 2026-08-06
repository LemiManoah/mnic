<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class VerifyExpense
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Expense $expense, Member $verifier, ?string $ipAddress = null): Expense
    {
        // Three distinct people: requester, approver, verifier.
        throw_if($expense->requested_by_member_id === $verifier->id, InvalidArgumentException::class, 'An expense cannot be verified by the member who requested it.');

        throw_if($expense->approved_by_member_id === $verifier->id, InvalidArgumentException::class, 'An expense cannot be verified by the member who approved it.');

        throw_if($expense->status !== ExpenseStatus::Paid, InvalidArgumentException::class, 'Only a paid expense can be verified.');

        return DB::transaction(function () use ($expense, $verifier, $ipAddress): Expense {
            $before = $expense->toArray();

            $expense->update([
                'status' => ExpenseStatus::Verified,
                'verified_by_member_id' => $verifier->id,
                'verified_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'expense.verified',
                $expense,
                $verifier,
                $before,
                $expense->toArray(),
                $ipAddress,
            );

            return $expense;
        });
    }
}
