<?php

declare(strict_types=1);

use App\Enums\ClubRole;

it('allows an administrator to view the audit log', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);

    $response = $this->actingAs($actor->user)->get(route('audit-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('audit-log/index'));
});

it('allows a secretary to view the audit log', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->get(route('audit-log.index'));

    $response->assertOk();
});

it('denies a treasurer from viewing the audit log', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);

    $response = $this->actingAs($actor->user)->get(route('audit-log.index'));

    $response->assertForbidden();
});

it('denies a plain member from viewing the audit log', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->get(route('audit-log.index'));

    $response->assertForbidden();
});
