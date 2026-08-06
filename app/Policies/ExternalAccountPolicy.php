<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\User;

final class ExternalAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::Treasurer->value,
            ClubRole::FinancialVerifier->value,
            ClubRole::Administrator->value,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(ClubRole::Administrator->value);
    }
}
