<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Self-registration tests (disabled)
|--------------------------------------------------------------------------
|
| Self-registration is switched off: the `register` / `register.store` routes
| are commented out in routes/web.php and members are onboarded by the
| Secretary instead. The tests below exercise those routes and are commented
| out with them. Restore this block when registration is re-enabled.
|
| it('renders registration page', ...)
| it('may register a new user', ...)
| it('requires name', ...)
| it('requires email', ...)
| it('requires valid email', ...)
| it('requires unique email', ...)
| it('requires password', ...)
| it('requires password confirmation', ...)
| it('requires matching password confirmation', ...)
| it('redirects authenticated users away from registration', ...)
|
*/

it('may delete user account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirectToRoute('home');

    expect($user->fresh())->toBeNull();

    $this->assertGuest();
});

it('requires password to delete account', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), []);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

it('requires correct password to delete account', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('user-profile.edit')
        ->delete(route('user.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertRedirectToRoute('user-profile.edit')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});
