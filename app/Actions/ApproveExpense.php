<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Member;
use App\Notifications\ExpenseDecided;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ApproveExpense
{
    public function __construct(
        private RecordAuditEvent $recordAuditEvent,
        private NotifyMembers $notifyMembers,
    ) {
        //
    }

    public function handle(Expense $expense, Member $approver, ?string $ipAddress = null): Expense
    {
        // Separation of duties: whoever asked for the money cannot approve it.
        throw_if($expense->requested_by_member_id === $approver->id, InvalidArgumentException::class, 'An expense cannot be approved by the member who requested it.');

        throw_if($expense->status !== ExpenseStatus::Submitted, InvalidArgumentException::class, 'Only a submitted expense can be approved.');

        DB::transaction(function () use ($expense, $approver, $ipAddress): Expense {
            $before = $expense->toArray();

            $expense->update([
                'status' => ExpenseStatus::Approved,
                'approved_by_member_id' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->recordAuditEvent->handle(
                'expense.approved',
                $expense,
                $approver,
                $before,
                $expense->toArray(),
                $ipAddress,
            );

            return $expense;
        });

        $this->notifyRequester($expense);

        return $expense;
    }

    private function notifyRequester(Expense $expense): void
    {
        $expense->loadMissing('requestedByMember.user');

        if ($expense->requestedByMember !== null) {
            $this->notifyMembers->handle(
                [$expense->requestedByMember],
                new ExpenseDecided($expense),
            );
        }
    }
}
