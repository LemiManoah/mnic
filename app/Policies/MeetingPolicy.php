<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ClubRole;
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

    /**
     * The secretary keeps the meeting record; the chair may convene.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::Secretary->value,
            ClubRole::InterimChairperson->value,
            ClubRole::Administrator->value,
        ]);
    }

    public function manageMinutes(User $user): bool
    {
        return $user->hasAnyRole([
            ClubRole::Secretary->value,
            ClubRole::Administrator->value,
        ]);
    }
}
