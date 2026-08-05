<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\User;

final class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Member $member): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function changeStatus(User $user, Member $member): bool
    {
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function assignRole(User $user, Member $member): bool
    {
        return $user->hasRole(ClubRole::Administrator->value);
    }
}
