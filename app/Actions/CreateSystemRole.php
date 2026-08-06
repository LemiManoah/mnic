<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class CreateSystemRole
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    /**
     * @param  list<string>  $permissions
     */
    public function handle(string $name, array $permissions, ?Member $actor = null, ?string $ipAddress = null): Role
    {
        return DB::transaction(function () use ($name, $permissions, $actor, $ipAddress): Role {
            $role = Role::query()->create([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);

            $this->recordAuditEvent->handle(
                'system_role.created',
                $role,
                $actor,
                null,
                ['name' => $name, 'permissions' => $permissions],
                $ipAddress,
            );

            resolve(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role;
        });
    }
}
