<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\Member;
use App\Models\User;

it('lists members and whether they can sign in', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    Member::factory()->create(['full_name' => 'No Login Member']);

    $response = $this->actingAs($actor->user)->get(route('user-management.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('user-management/index')
            ->has('members', 2)
            ->has('roles'));
});

it('denies a plain member from managing logins', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $this->actingAs($actor->user)->get(route('user-management.index'))->assertForbidden();
});

it('creates a login for a member who has none', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('user-management.store'), [
        'member_id' => $member->id,
        'email' => 'new.member@example.com',
        'role' => ClubRole::Treasurer->value,
    ]);

    $response->assertRedirectToRoute('user-management.index');

    $user = User::query()->where('email', 'new.member@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($member->fresh()?->user_id)->toBe($user?->id)
        ->and($user?->hasRole(ClubRole::Treasurer->value))->toBeTrue()
        // Created by an officer in person, so there is nothing to verify.
        ->and($user?->email_verified_at)->not->toBeNull();
});

it('refuses to give a member a second login', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $member = memberWithRole(ClubRole::Member);

    $this->actingAs($actor->user)
        ->post(route('user-management.store'), [
            'member_id' => $member->id,
            'email' => 'second.login@example.com',
            'role' => ClubRole::Member->value,
        ])
        ->assertSessionHasErrors('member_id');
});

it('rejects a duplicate email and an unknown role', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $member = Member::factory()->create();

    $this->actingAs($actor->user)
        ->post(route('user-management.store'), [
            'member_id' => $member->id,
            'email' => (string) $actor->user?->email,
            'role' => ClubRole::Member->value,
        ])
        ->assertSessionHasErrors('email');

    $this->actingAs($actor->user)
        ->post(route('user-management.store'), [
            'member_id' => $member->id,
            'email' => 'fine@example.com',
            'role' => 'wizard',
        ])
        ->assertSessionHasErrors('role');
});
