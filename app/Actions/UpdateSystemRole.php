<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClubRole;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class UpdateSystemRole
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  list<string>  $permissions
     */
    public function handle(Role $role, string $name, array $permissions, ?Member $actor = null, ?string $ipAddress = null): Role
    {
        // Renaming the administrator role would break the Gate::before bypass
        // and could lock every administrator out of the application.
        throw_if(
            $role->name === ClubRole::Administrator->value && $name !== $role->name,
            InvalidArgumentException::class,
            'The administrator role cannot be renamed.',
        );

        return DB::transaction(function () use ($role, $name, $permissions, $actor, $ipAddress): Role {
            $before = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];

            $role->update(['name' => $name]);
            $role->syncPermissions($permissions);

            $this->recordAuditEvent->handle(
                'system_role.updated',
                $role,
                $actor,
                $before,
                ['name' => $name, 'permissions' => $permissions],
                $ipAddress,
            );

            resolve(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role;
        });
    }
}
