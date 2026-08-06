<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ClubRole;
use App\Models\User;
use App\Policies\Concerns\EnforcesBusinessRules;
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
         * Administrators bypass permission checks, but never business rules.
         *
         * The bypass exists so an administrator is never locked out by a
         * missing grant. It must not extend to policies that also decide on
         * state — whether a payment is still awaiting review, whether a month
         * is locked, whether this member already voted — because those answers
         * are the same for everybody. Policies marked EnforcesBusinessRules are
         * therefore left to run, and administrators pass them on the permissions
         * they already hold.
         *
         * Maker-checker rules are enforced a second time inside the Actions, so
         * even a mistake here cannot let anyone verify their own payment.
         */
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (! $user->hasRole(ClubRole::Administrator->value)) {
                return null;
            }

            $subject = $arguments[0] ?? null;

            // Abilities checked without a subject — Gate::authorize('users.manage')
            // and the like — are pure permission questions, so bypass them.
            if (! is_object($subject) && ! is_string($subject)) {
                return true;
            }

            return Gate::getPolicyFor($subject) instanceof EnforcesBusinessRules
                ? null
                : true;
        });

        // Spatie's Role lives outside App\Models, so convention-based policy
        // discovery does not find SystemRolePolicy on its own.
        Gate::policy(Role::class, SystemRolePolicy::class);
    }
}
