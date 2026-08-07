<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Member;
use App\Models\PeriodAdjustment;
use App\Models\User;
use App\Policies\Concerns\EnforcesBusinessRules;

/**
 * Carries EnforcesBusinessRules: "has this already been decided" and "did you
 * raise it yourself" are state questions, and an administrator must not be able
 * to answer both halves of a two-signature correction.
 */
final class PeriodAdjustmentPolicy implements EnforcesBusinessRules
{
    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AdjustmentsRequest->value);
    }

    public function review(User $user, PeriodAdjustment $adjustment): bool
    {
        if (! $adjustment->isPending()) {
            return false;
        }

        if (! $user->can(Permission::AdjustmentsApprove->value)) {
            return false;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $adjustment->requested_by_member_id !== $member->id;
    }
}
