<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RejectExpense
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Expense $expense, Member $approver, string $reason, ?string $ipAddress = null): Expense
    {
        throw_if($expense->requested_by_member_id === $approver->id, InvalidArgumentException::class, 'An expense cannot be reviewed by the member who requested it.');

        throw_if($expense->status !== ExpenseStatus::Submitted, InvalidArgumentException::class, 'Only a submitted expense can be rejected.');

        return DB::transaction(function () use ($expense, $approver, $reason, $ipAddress): Expense {
            $before = $expense->toArray();

            $expense->update([
                'status' => ExpenseStatus::Rejected,
                'rejection_reason' => $reason,
                'approved_by_member_id' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'expense.rejected',
                $expense,
                $approver,
                $before,
                $expense->toArray(),
                $ipAddress,
            );

            return $expense;
        });
    }
}
