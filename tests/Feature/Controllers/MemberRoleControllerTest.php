<?php

declare(strict_types=1);

use App\Enums\ClubRole;

it('allows an administrator to assign a role', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $member = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->put(route('member-role.update', $member), [
        'role' => ClubRole::Treasurer->value,
    ]);

    $response->assertRedirectToRoute('member.index');

    expect($member->user?->fresh()?->hasRole(ClubRole::Treasurer->value))->toBeTrue();
});

it('denies a secretary from assigning a role', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->put(route('member-role.update', $member), [
        'role' => ClubRole::Treasurer->value,
    ]);

    $response->assertForbidden();
});
