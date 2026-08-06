<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\ActionItem;
use App\Models\Member;
use App\Models\User;

final class ActionItemPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::InterimChairperson->value,
            ClubRole::Secretary->value,
            ClubRole::Administrator->value,
        ]);
    }

    /**
     * Officers may retarget any action; the owner may update their own.
     */
    public function update(User $user, ActionItem $actionItem): bool
    {
        if ($user->hasAnyRole([
            ClubRole::InterimChairperson->value,
            ClubRole::Secretary->value,
            ClubRole::Administrator->value,
        ])) {
            return true;
        }

        $member = Member::query()->firstWhere('user_id', $user->id);

        return $member !== null && $actionItem->owner_member_id === $member->id;
    }
}
