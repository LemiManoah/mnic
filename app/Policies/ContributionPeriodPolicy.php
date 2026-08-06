<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\User;

final class ContributionPeriodPolicy
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
        return $user->hasAnyRole([ClubRole::Treasurer->value, ClubRole::Administrator->value]);
    }
}
