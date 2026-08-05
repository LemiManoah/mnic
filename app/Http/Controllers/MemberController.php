<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ChangeMemberStatus;
use App\Actions\CreateMember;
use App\Actions\UpdateMember;
use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Http\Requests\CreateMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class MemberController
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Member::class);

        return Inertia::render('member/index', [
            'members' => Member::query()->latest()->paginate(20),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Member::class);

        return Inertia::render('member/create');
    }

    public function store(CreateMemberRequest $request, #[CurrentUser] User $user, CreateMember $action): RedirectResponse
    {
        $action->handle(
            $request->validated(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member created.'),
        ]);

        return to_route('member.index');
    }

    public function edit(Member $member, #[CurrentUser] User $user): Response
    {
        Gate::authorize('update', $member);

        $member->loadMissing('user');

        return Inertia::render('member/edit', [
            'member' => $member,
            'canAssignRole' => $user->can('assignRole', $member),
            'currentRole' => $member->user?->getRoleNames()->first(),
            'roleOptions' => array_map(
                static fn (ClubRole $role): array => ['value' => $role->value, 'label' => $role->label()],
                ClubRole::cases(),
            ),
            'statusOptions' => array_map(
                static fn (MemberStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ChangeMemberStatus::allowedTransitionsFrom($member->status),
            ),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member, UpdateMember $action): RedirectResponse
    {
        $action->handle($member, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Member updated.'),
        ]);

        return to_route('member.index');
    }
}
