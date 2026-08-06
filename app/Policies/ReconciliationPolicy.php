<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\ReconciliationStatus;
use App\Models\Member;
use App\Models\Reconciliation;
use App\Models\User;

final class ReconciliationPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ReconciliationsCreate->value);
    }

    /**
     * A locked month can never be edited — corrections go through a reversal.
     */
    public function update(User $user, Reconciliation $reconciliation): bool
    {
        return ! $reconciliation->isLocked()
            && $reconciliation->status->isEditable()
            && $user->can(Permission::ReconciliationsUpdate->value);
    }

    /**
     * Maker-checker: whoever prepared it cannot confirm it. Re-checked inside
     * ConfirmReconciliation.
     */
    public function confirm(User $user, Reconciliation $reconciliation): bool
    {
        if ($reconciliation->status !== ReconciliationStatus::Submitted) {
            return false;
        }

        if (! $user->can(Permission::ReconciliationsConfirm->value)) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $reconciliation->prepared_by_member_id !== $member->id;
    }

    public function lock(User $user, Reconciliation $reconciliation): bool
    {
        return $reconciliation->status === ReconciliationStatus::Confirmed
            && $user->can(Permission::ReconciliationsLock->value);
    }
}
