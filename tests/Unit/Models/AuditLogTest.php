<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Member;

test('to array', function (): void {
    $auditLog = AuditLog::factory()->create()->refresh();

    expect(array_keys($auditLog->toArray()))
        ->toBe([
            'id',
            'actor_member_id',
            'event',
            'auditable_type',
            'auditable_id',
            'before',
            'after',
            'ip_address',
            'created_at',
            'updated_at',
        ]);
});

it('belongs to an actor member', function (): void {
    $member = Member::factory()->create();
    $auditLog = AuditLog::factory()->create(['actor_member_id' => $member->id]);

    expect($auditLog->actorMember->is($member))->toBeTrue();
});

it('resolves the polymorphic auditable record', function (): void {
    $subject = Member::factory()->create();

    $auditLog = AuditLog::factory()->create([
        'auditable_type' => Member::class,
        'auditable_id' => $subject->id,
    ]);

    expect($auditLog->auditable)->toBeInstanceOf(Member::class)
        ->and($auditLog->auditable->is($subject))->toBeTrue();
});
