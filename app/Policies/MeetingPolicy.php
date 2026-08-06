<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class MeetingPolicy
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
        return $user->can(Permission::MeetingsCreate->value);
    }

    public function manageMinutes(User $user): bool
    {
        return $user->can(Permission::MeetingsManageMinutes->value);
    }
}
