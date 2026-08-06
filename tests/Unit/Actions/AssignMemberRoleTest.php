<?php

declare(strict_types=1);

use App\Actions\AssignMemberRole;
use App\Enums\ClubRole;
use App\Models\AuditLog;
use App\Models\Member;
use Database\Seeders\RolePermissionSeeder;

it('assigns a role to the member linked user and records an audit event', function (): void {
    (new RolePermissionSeeder)->run();

    $member = memberWithRole(ClubRole::Member);
    $actor = Member::factory()->create();

    $action = resolve(AssignMemberRole::class);

    $action->handle($member, ClubRole::Treasurer, $actor, '127.0.0.1');

    expect($member->user?->fresh()?->hasRole(ClubRole::Treasurer->value))->toBeTrue()
        ->and($member->user?->fresh()?->hasRole(ClubRole::Member->value))->toBeFalse();

    $auditLog = AuditLog::query()->where('auditable_id', $member->id)->first();

    expect($auditLog?->event)->toBe('member.role_assigned')
        ->and($auditLog?->actor_member_id)->toBe($actor->id);
});

it('cannot assign a role to a member without a linked user', function (): void {
    (new RolePermissionSeeder)->run();

    $member = Member::factory()->create();

    $action = resolve(AssignMemberRole::class);

    $action->handle($member, ClubRole::Treasurer);
})->throws(RuntimeException::class);
