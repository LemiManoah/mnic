<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ClubRole;
use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the permission catalogue and the club's default system roles.
 *
 * Roles are data from here on — an administrator may create, rename or delete
 * them. These six are only the starting bundles, chosen to reproduce the
 * behaviour the application had when roles were a hard-coded enum.
 *
 * The administrator role is seeded with every permission for completeness, but
 * in practice it never needs them: AppServiceProvider grants administrators a
 * blanket bypass.
 */
final class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<PermissionEnum>>
     */
    private const array ROLE_PERMISSIONS = [
        // An ordinary member needs no permissions: reading the roster, viewing
        // periods and submitting their own payment are open to everyone.
        'member' => [],

        'interim-chairperson' => [
            PermissionEnum::MeetingsCreate,
            PermissionEnum::ProposalsCreate,
            PermissionEnum::ProposalsManageVoting,
            PermissionEnum::ActionItemsCreate,
            PermissionEnum::ActionItemsUpdateAny,
            PermissionEnum::ExpensesCreate,
            PermissionEnum::ExpensesApprove,
        ],

        'secretary' => [
            PermissionEnum::MembersCreate,
            PermissionEnum::MembersUpdate,
            PermissionEnum::MembersChangeStatus,
            PermissionEnum::AuditView,
            PermissionEnum::MeetingsCreate,
            PermissionEnum::MeetingsManageMinutes,
            PermissionEnum::ProposalsCreate,
            PermissionEnum::ProposalsManageVoting,
            PermissionEnum::ActionItemsCreate,
            PermissionEnum::ActionItemsUpdateAny,
            PermissionEnum::ExpensesCreate,
        ],

        'treasurer' => [
            PermissionEnum::ContributionPeriodsCreate,
            PermissionEnum::PaymentsViewEvidence,
            PermissionEnum::ExpensesCreate,
            PermissionEnum::ExpensesPay,
            PermissionEnum::ExternalAccountsView,
            PermissionEnum::ReconciliationsCreate,
            PermissionEnum::ReconciliationsUpdate,
            PermissionEnum::ReconciliationsLock,
        ],

        'financial-verifier' => [
            PermissionEnum::PaymentsReview,
            PermissionEnum::PaymentsViewEvidence,
            PermissionEnum::ExpensesVerify,
            PermissionEnum::ReconciliationsConfirm,
            PermissionEnum::ExternalAccountsView,
        ],

        'administrator' => [],
    ];

    public function run(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions(
                $roleName === ClubRole::Administrator->value
                    ? array_map(static fn (PermissionEnum $case): string => $case->value, PermissionEnum::cases())
                    : array_map(static fn (PermissionEnum $case): string => $case->value, $permissions),
            );
        }

        // Spatie caches the permission map; without this the roles just seeded
        // are invisible for the rest of the request.
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
