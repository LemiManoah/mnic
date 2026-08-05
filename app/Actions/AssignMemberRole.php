<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClubRole;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class AssignMemberRole
{
    public function __construct(private RecordAuditEvent $recordAuditEvent)
    {
        //
    }

    public function handle(Member $member, ClubRole $role, ?Member $actor = null, ?string $ipAddress = null): void
    {
        $member->loadMissing('user');
        $user = $member->user;

        if ($user === null) {
            throw new RuntimeException('Cannot assign a role to a member without a linked user account.');
        }

        DB::transaction(function () use ($member, $user, $role, $actor, $ipAddress): void {
            $before = ['roles' => $user->getRoleNames()->all()];

            $user->syncRoles([$role->value]);

            $this->recordAuditEvent->handle('member.role_assigned', $member, $actor, $before, ['roles' => [$role->value]], $ipAddress);
        });
    }
}
