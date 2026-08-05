<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\User;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([ClubRole::Administrator->value, ClubRole::Secretary->value]);
    }
}
