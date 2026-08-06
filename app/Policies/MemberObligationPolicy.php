<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\MemberObligation;
use App\Models\User;

final class MemberObligationPolicy
{
    public function adjust(User $user, MemberObligation $obligation): bool
    {
        return $user->can(Permission::ObligationsAdjust->value);
    }
}
