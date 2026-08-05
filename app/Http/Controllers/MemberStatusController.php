<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ChangeMemberStatus;
use App\Enums\MemberStatus;
use App\Http\Requests\ChangeMemberStatusRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class MemberStatusController
{
    public function update(ChangeMemberStatusRequest $request, Member $member, #[CurrentUser] User $user, ChangeMemberStatus $action): RedirectResponse
    {
        $action->handle(
            $member,
            MemberStatus::from($request->string('to_status')->value()),
            $request->string('reason')->value(),
            $request->string('effective_date')->value(),
            $request->filled('resolution_reference') ? $request->string('resolution_reference')->value() : null,
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member status updated.'),
        ]);

        return to_route('member.index');
    }
}
