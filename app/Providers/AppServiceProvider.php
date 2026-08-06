<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ClubRole;
use App\Models\User;
use App\Policies\SystemRolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /**
         * Administrators bypass every policy check.
         *
         * This covers authorisation only. The maker-checker rules live inside
         * the Actions — an administrator still cannot verify a payment they
         * recorded, or approve their own expense; those attempts fail in the
         * Action rather than at the gate.
         */
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (! $user->hasRole(ClubRole::Administrator->value)) {
                return null;
            }

            // System roles are the one exception to the bypass. Their policy
            // carries structural guards — the administrator role can never be
            // renamed or deleted, because Gate::before itself depends on it —
            // and a bypass would skip those and fail later as a 500 instead of
            // a clean 403. Administrators hold every permission explicitly
            // anyway, so nothing they should be able to do is lost.
            $subject = $arguments[0] ?? null;

            if ($subject instanceof Role || $subject === Role::class) {
                return null;
            }

            return true;
        });

        // Spatie's Role lives outside App\Models, so convention-based policy
        // discovery does not find SystemRolePolicy on its own.
        Gate::policy(Role::class, SystemRolePolicy::class);
    }
}
