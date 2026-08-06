<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Models\Expense;
use App\Models\Member;
use App\Models\User;

final class ExpensePolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ExpensesCreate->value);
    }

    /**
     * Separation of duties: never the member who requested the money. As with
     * payments, that half is a row-level rule and is re-checked in the Action.
     */
    public function approve(User $user, Expense $expense): bool
    {
        if ($expense->status !== ExpenseStatus::Submitted) {
            return false;
        }

        if (! $user->can(Permission::ExpensesApprove->value)) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $expense->requested_by_member_id !== $member->id;
    }

    public function pay(User $user, Expense $expense): bool
    {
        return $expense->status === ExpenseStatus::Approved
            && $user->can(Permission::ExpensesPay->value);
    }

    /**
     * The third pair of eyes: not the requester, not the approver.
     */
    public function verify(User $user, Expense $expense): bool
    {
        if ($expense->status !== ExpenseStatus::Paid) {
            return false;
        }

        if (! $user->can(Permission::ExpensesVerify->value)) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null
            && $expense->requested_by_member_id !== $member->id
            && $expense->approved_by_member_id !== $member->id;
    }
}
