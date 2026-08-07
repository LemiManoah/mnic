<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\MeetingStatus;
use App\Enums\Permission;
use App\Models\Meeting;
use App\Models\User;
use App\Policies\Concerns\EnforcesBusinessRules;

/**
 * Carries EnforcesBusinessRules because `cancel` asks whether the meeting has
 * already happened, and that answer must not change for an administrator. The
 * administrator role holds every permission anyway, so it loses nothing.
 */
final class MeetingPolicy implements EnforcesBusinessRules
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

    /**
     * Only a meeting that has not happened yet. Attendance and minutes hang off
     * a meeting that was held, and cancelling it would strand them.
     */
    public function cancel(User $user, Meeting $meeting): bool
    {
        return $meeting->status === MeetingStatus::Scheduled
            && $user->can(Permission::MeetingsCreate->value);
    }

    /**
     * Correcting confirmed minutes is deliberately the same permission as
     * publishing them — the safeguard is that a correction supersedes rather
     * than overwrites, not that fewer people may do it.
     */
    public function correctMinutes(User $user): bool
    {
        return $user->can(Permission::MeetingsManageMinutes->value);
    }
}
