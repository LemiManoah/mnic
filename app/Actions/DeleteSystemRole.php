<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClubRole;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class DeleteSystemRole
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Role $role, ?Member $actor = null, ?string $ipAddress = null): void
    {
        throw_if(
            $role->name === ClubRole::Administrator->value,
            InvalidArgumentException::class,
            'The administrator role cannot be deleted.',
        );

        // Deleting a role that people still hold would silently strip their
        // access, so make the caller reassign them first.
        throw_if(
            $role->users()->exists(),
            InvalidArgumentException::class,
            'This role is still assigned to at least one account.',
        );

        DB::transaction(function () use ($role, $actor, $ipAddress): void {
            $before = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];

            $this->recordAuditEvent->handle(
                'system_role.deleted',
                $role,
                $actor,
                $before,
                null,
                $ipAddress,
            );

            $role->delete();

            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }
}
