<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateSystemRole;
use App\Actions\DeleteSystemRole;
use App\Actions\UpdateSystemRole;
use App\Enums\ClubRole;
use App\Enums\Permission as PermissionEnum;
use App\Http\Requests\CreateSystemRoleRequest;
use App\Http\Requests\UpdateSystemRoleRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class SystemRoleController
{
    public function index(#[CurrentUser] User $user): Response
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('system-role/index', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->getKey(),
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name')->all(),
                    // The administrator role underpins the Gate bypass, so it
                    // is protected from deletion and renaming.
                    'is_protected' => $role->name === ClubRole::Administrator->value,
                    'can_delete' => $user->can('delete', $role),
                ]),
            'permissionGroups' => collect(PermissionEnum::cases())
                ->groupBy(fn (PermissionEnum $permission): string => $permission->group())
                ->map(fn ($permissions): array => $permissions
                    ->map(fn (PermissionEnum $permission): array => [
                        'value' => $permission->value,
                        'label' => $permission->label(),
                    ])
                    ->values()
                    ->all())
                ->toArray(),
        ]);
    }

    public function store(
        CreateSystemRoleRequest $request,
        #[CurrentUser] User $user,
        CreateSystemRole $action,
    ): RedirectResponse {
        /** @var list<string> $permissions */
        $permissions = $request->validated('permissions') ?? [];

        $action->handle(
            $request->string('name')->value(),
            $permissions,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('system-role.index');
    }

    public function update(
        UpdateSystemRoleRequest $request,
        Role $role,
        #[CurrentUser] User $user,
        UpdateSystemRole $action,
    ): RedirectResponse {
        /** @var list<string> $permissions */
        $permissions = $request->validated('permissions') ?? [];

        $action->handle(
            $role,
            $request->string('name')->value(),
            $permissions,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('system-role.index');
    }

    public function destroy(
        Request $request,
        Role $role,
        #[CurrentUser] User $user,
        DeleteSystemRole $action,
    ): RedirectResponse {
        Gate::authorize('delete', $role);

        $action->handle(
            $role,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('system-role.index');
    }
}
