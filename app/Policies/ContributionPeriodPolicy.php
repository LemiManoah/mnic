<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
use App\Models\ContributionPeriod;
use App\Models\User;

final class ContributionPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContributionPeriod $contributionPeriod): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([ClubRole::Treasurer->value, ClubRole::Administrator->value]);
    }
}
