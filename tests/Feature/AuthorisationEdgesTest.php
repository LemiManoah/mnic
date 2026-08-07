<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\Permission;
use App\Models\PositionPoll;
use App\Models\Proposal;
use App\Models\Reconciliation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;

/**
 * Authorisation cases that the ordinary workflows never reach: a login with no
 * member record behind it, and the administrator bypass on an ability that has
 * no subject.
 */
it('refuses a vote from a login with no member record', function (): void {
    (new RolePermissionSeeder)->run();

    // A user can exist without a member — the user management screen creates
    // the login, and nothing forces a member row to follow.
    $user = User::factory()->withoutTwoFactor()->create();
    $user->assignRole(ClubRole::Member->value);

    $proposal = Proposal::factory()->open()->create();
    $poll = PositionPoll::factory()->open()->create();

    expect($user->can('vote', $proposal))->toBeFalse()
        ->and($user->can('vote', $poll))->toBeFalse();
});

it('lets any member view a reconciliation', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    expect($actor->user?->can('view', Reconciliation::factory()->create()))->toBeTrue();
});

it('refuses confirmation from someone without the confirm permission', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);

    // The treasurer prepares reconciliations but deliberately cannot confirm
    // one — that is the separation of duties, not a missing grant.
    $reconciliation = Reconciliation::factory()->submitted()->create();

    expect($actor->user?->can(Permission::ReconciliationsConfirm->value))->toBeFalse()
        ->and($actor->user?->can('confirm', $reconciliation))->toBeFalse();
});

it('bypasses abilities that have no subject for an administrator', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    // Gate::before short-circuits here because there is no model to ask a
    // state question about — it is purely "may you?".
    expect(Gate::forUser($actor->user)->allows(Permission::UsersManage->value))->toBeTrue();
});
