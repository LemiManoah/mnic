<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ActionItem;
use App\Models\Member;
use App\Models\User;
use App\Policies\Concerns\EnforcesBusinessRules;

final class ActionItemPolicy implements EnforcesBusinessRules
{
    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ActionItemsCreate->value);
    }

    /**
     * Officers may retarget any action; the owner may always update their own.
     */
    public function update(User $user, ActionItem $actionItem): bool
    {
        if ($user->can(Permission::ActionItemsUpdateAny->value)) {
            return true;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $actionItem->owner_member_id === $member->id;
    }
}
