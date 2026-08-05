<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteUser;
use App\Http\Requests\DeleteUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final readonly class UserController
{
    /*
    |--------------------------------------------------------------------------
    | Self-registration (disabled)
    |--------------------------------------------------------------------------
    |
    | The `register` / `register.store` routes are commented out in
    | routes/web.php; members are onboarded by the Secretary instead. These
    | actions are kept here for an easy restore. Re-enabling them also means
    | restoring their imports (CreateUser, CreateUserRequest, Inertia,
    | Response) and the tests in tests/Feature/Controllers/UserControllerTest.
    |
    | public function create(): Response
    | {
    |     return Inertia::render('user/create');
    | }
    |
    | public function store(CreateUserRequest $request, CreateUser $action): RedirectResponse
    | {
    |     $attributes = $request->safe()->except('password');
    |
    |     $user = $action->handle($attributes, $request->string('password')->value());
    |
    |     Auth::login($user);
    |     $request->session()->regenerate();
    |
    |     return redirect()->intended(route('dashboard', absolute: false));
    | }
    |
    */

    public function destroy(DeleteUserRequest $request, #[CurrentUser] User $user, DeleteUser $action): RedirectResponse
    {
        Auth::logout();

        $action->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
