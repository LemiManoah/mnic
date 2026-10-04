<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\AuditLog;

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


it('finds event codes using readable words', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    AuditLog::factory()->create(['event' => 'action_item.created']);
    AuditLog::factory()->create(['event' => 'payment.verified']);

    $this->actingAs($actor->user)->get(route('audit-log.index', ['search' => 'action item created']))
        ->assertOk()->assertInertia(fn ($page) => $page
            ->has('auditLogs.data', 1)
            ->where('auditLogs.data.0.event', 'action_item.created'));
});
