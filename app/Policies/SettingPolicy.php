<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\User;

final class SettingPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return $user->hasRole(ClubRole::Administrator->value);
    }
}
