<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

final class MemberObligationPolicy
{
    public function adjust(User $user): bool
    {
        return $user->can(Permission::ObligationsAdjust->value);
    }
}
