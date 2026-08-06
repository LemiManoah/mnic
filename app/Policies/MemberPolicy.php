<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\User;

final class MemberPolicy
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
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function changeStatus(User $user): bool
    {
        return $user->hasAnyRole([ClubRole::Secretary->value, ClubRole::Administrator->value]);
    }

    public function assignRole(User $user): bool
    {
        return $user->hasRole(ClubRole::Administrator->value);
    }
}
