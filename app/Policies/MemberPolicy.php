<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
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
        return $user->can(Permission::MembersCreate->value);
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::MembersUpdate->value);
    }

    public function changeStatus(User $user): bool
    {
        return $user->can(Permission::MembersChangeStatus->value);
    }

    public function assignRole(User $user): bool
    {
        return $user->can(Permission::MembersAssignRole->value);
    }
}
