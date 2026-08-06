<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class SettingPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::SettingsUpdate->value);
    }
}
