<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Enums\Permission;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class SystemRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RolesManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RolesManage->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesManage->value);
    }

    /**
     * The administrator role is structural — the Gate::before bypass depends
     * on it — so it is never deletable, whatever permissions the caller holds.
     */
    public function delete(User $user, Role $role): bool
    {
        return $role->name !== ClubRole::Administrator->value
            && $user->can(Permission::RolesManage->value);
    }
}
