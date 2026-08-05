<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AssignMemberRole;
use App\Enums\ClubRole;
use App\Http\Requests\AssignMemberRoleRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MemberRoleController
{
    public function update(AssignMemberRoleRequest $request, Member $member, #[CurrentUser] User $user, AssignMemberRole $action): RedirectResponse
    {
        $action->handle(
            $member,
            ClubRole::from($request->string('role')->value()),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member role updated.'),
        ]);

        return to_route('member.index');
    }
}
