<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Enums\ExpenseStatus;
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
        return $user->hasAnyRole([
            ClubRole::Treasurer->value,
            ClubRole::Secretary->value,
            ClubRole::InterimChairperson->value,
            ClubRole::Administrator->value,
        ]);
    }

    /**
     * Approval sits with the chair or an administrator, and never with the
     * member who requested the money.
     */
    public function approve(User $user, Expense $expense): bool
    {
        if ($expense->status !== ExpenseStatus::Submitted) {
            return false;
        }

        if (! $user->hasAnyRole([ClubRole::InterimChairperson->value, ClubRole::Administrator->value])) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $expense->requested_by_member_id !== $member->id;
    }

    public function pay(User $user, Expense $expense): bool
    {
        return $expense->status === ExpenseStatus::Approved
            && $user->hasAnyRole([ClubRole::Treasurer->value, ClubRole::Administrator->value]);
    }

    /**
     * Verification is the third pair of eyes: not the requester, not the
     * approver.
     */
    public function verify(User $user, Expense $expense): bool
    {
        if ($expense->status !== ExpenseStatus::Paid) {
            return false;
        }

        if (! $user->hasAnyRole([ClubRole::FinancialVerifier->value, ClubRole::Administrator->value])) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null
            && $expense->requested_by_member_id !== $member->id
            && $expense->approved_by_member_id !== $member->id;
    }
}
