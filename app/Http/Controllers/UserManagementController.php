<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserForMember;
use App\Enums\Permission;
use App\Http\Requests\CreateUserForMemberRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class UserManagementController
{
    public function index(): Response
    {
        Gate::authorize(Permission::UsersManage->value);

        return Inertia::render('user-management/index', [
            'members' => Member::query()
                ->with('user')
                ->orderBy('member_number')
                ->get()
                ->map(fn (Member $member): array => [
                    'id' => $member->id,
                    'member_number' => $member->member_number,
                    'full_name' => $member->full_name,
                    'status' => $member->status,
                    'email' => $member->user?->email,
                    'role' => $member->user?->getRoleNames()->first(),
                    'has_login' => $member->user_id !== null,
                ]),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(
        CreateUserForMemberRequest $request,
        #[CurrentUser] User $user,
        CreateUserForMember $action,
    ): RedirectResponse {
        $member = Member::query()->findOrFail($request->string('member_id')->value());

        $result = $action->handle(
            $member,
            $request->string('email')->value(),
            $request->string('role')->value(),
            Member::query()->firstWhere('user_id', $user->id),
            $request->ip(),
        );

        // Shown once, so the officer can pass it on. It is never recoverable
        // afterwards — a forgotten password has to be reset.
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Login created for :name. Temporary password: :password', [
                'name' => $member->full_name,
                'password' => $result['password'],
            ]),
        ]);

        return to_route('user-management.index');
    }
}
