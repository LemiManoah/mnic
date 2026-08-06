<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class ExternalAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ExternalAccountsView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ExternalAccountsCreate->value);
    }
}
