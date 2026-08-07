<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserForMember;
use App\Enums\Permission;
use App\Http\Requests\CreateUserForMemberRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final readonly class UserManagementController
{
    public function index(Request $request): Response
    {
        Gate::authorize(Permission::UsersManage->value);

        $search = $request->string('search')->trim()->value();
        $login = $request->string('login')->value();

        return Inertia::render('user-management/index', [
            'members' => Member::query()
                ->with('user')
                ->when($search !== '', fn (Builder $query): Builder => $query
                    ->where(fn (Builder $inner): Builder => $inner
                        ->where('full_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('member_number', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('user', fn (Builder $user): Builder => $user
                            ->where('email', 'like', sprintf('%%%s%%', $search)))))
                ->when($login === 'with', fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                ->when($login === 'without', fn (Builder $query): Builder => $query->whereNull('user_id'))
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
            'filters' => [
                'search' => $search === '' ? null : $search,
                'login' => $login === '' ? null : $login,
            ],
            'loginOptions' => [
                ['value' => 'with', 'label' => 'Has a login'],
                ['value' => 'without', 'label' => 'No login yet'],
            ],
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
