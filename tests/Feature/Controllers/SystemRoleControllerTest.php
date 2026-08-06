<?php

declare(strict_types=1);

use App\Actions\DeleteSystemRole;
use App\Actions\UpdateSystemRole;
use App\Enums\ClubRole;
use App\Enums\Permission;
use Spatie\Permission\Models\Role;

it('lists roles for an administrator', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $response = $this->actingAs($actor->user)->get(route('system-role.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('system-role/index')
            ->has('roles')
            ->has('permissionGroups'));
});

it('denies a plain member from managing roles', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $this->actingAs($actor->user)->get(route('system-role.index'))->assertForbidden();
});

it('creates a role with the permissions chosen at creation', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $response = $this->actingAs($actor->user)->post(route('system-role.store'), [
        'name' => 'deputy-treasurer',
        'permissions' => [Permission::ExpensesPay->value],
    ]);

    $response->assertRedirectToRoute('system-role.index');

    $role = Role::query()->where('name', 'deputy-treasurer')->first();

    expect($role)->not->toBeNull()
        ->and($role?->hasPermissionTo(Permission::ExpensesPay->value))->toBeTrue()
        ->and($role?->hasPermissionTo(Permission::ExpensesApprove->value))->toBeFalse();
});

it('rejects a role name that is not a slug', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $this->actingAs($actor->user)
        ->post(route('system-role.store'), ['name' => 'Deputy Treasurer'])
        ->assertSessionHasErrors('name');
});

it('rejects a duplicate role name and an unknown permission', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $this->actingAs($actor->user)
        ->post(route('system-role.store'), ['name' => 'treasurer'])
        ->assertSessionHasErrors('name');

    $this->actingAs($actor->user)
        ->post(route('system-role.store'), [
            'name' => 'inventor',
            'permissions' => ['money.print'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

it('updates a role and its permissions', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $role = Role::query()->where('name', 'treasurer')->firstOrFail();

    $response = $this->actingAs($actor->user)->put(route('system-role.update', $role), [
        'name' => 'club-treasurer',
        'permissions' => [Permission::ExpensesPay->value],
    ]);

    $response->assertRedirectToRoute('system-role.index');

    expect($role->fresh()?->name)->toBe('club-treasurer')
        ->and($role->fresh()?->hasPermissionTo(Permission::ContributionPeriodsCreate->value))->toBeFalse();
});

it('refuses to rename the administrator role', function (): void {
    // Exercised at the Action, because Laravel turns a thrown exception into a
    // 500 response rather than letting it surface through an HTTP test.
    memberWithRole(ClubRole::Administrator);
    $role = Role::query()->where('name', ClubRole::Administrator->value)->firstOrFail();

    resolve(UpdateSystemRole::class)->handle($role, 'super-admin', []);
})->throws(InvalidArgumentException::class);

it('deletes an unused role', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $role = Role::query()->create(['name' => 'observer', 'guard_name' => 'web']);

    $response = $this->actingAs($actor->user)->delete(route('system-role.destroy', $role));

    $response->assertRedirectToRoute('system-role.index');

    expect(Role::query()->where('name', 'observer')->exists())->toBeFalse();
});

it('refuses to delete the administrator role', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $role = Role::query()->where('name', ClubRole::Administrator->value)->firstOrFail();

    $this->actingAs($actor->user)
        ->delete(route('system-role.destroy', $role))
        ->assertForbidden();

    expect(Role::query()->where('name', ClubRole::Administrator->value)->exists())->toBeTrue();
});

it('refuses to delete a role that is still assigned', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $role = Role::query()->where('name', ClubRole::Secretary->value)->firstOrFail();

    expect($actor->user?->hasRole($role->name))->toBeTrue();

    resolve(DeleteSystemRole::class)->handle($role);
})->throws(InvalidArgumentException::class);
