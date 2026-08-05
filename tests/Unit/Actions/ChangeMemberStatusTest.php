<?php

declare(strict_types=1);

use App\Actions\ChangeMemberStatus;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MembershipStatusHistory;

it('allows a valid transition and records history and audit', function (): void {
    $member = Member::factory()->create(['status' => MemberStatus::Active]);
    $actor = Member::factory()->create();

    $action = resolve(ChangeMemberStatus::class);

    $updated = $action->handle(
        $member,
        MemberStatus::Suspended,
        'Missed contributions',
        now()->toDateString(),
        null,
        $actor,
        '10.0.0.1',
    );

    expect($updated->status)->toBe(MemberStatus::Suspended);

    $history = MembershipStatusHistory::query()->where('member_id', $member->id)->first();

    expect($history?->from_status)->toBe(MemberStatus::Active)
        ->and($history?->to_status)->toBe(MemberStatus::Suspended)
        ->and($history?->reason)->toBe('Missed contributions');

    $auditLog = AuditLog::query()->where('auditable_id', $member->id)->first();

    expect($auditLog?->event)->toBe('member.status_changed')
        ->and($auditLog?->actor_member_id)->toBe($actor->id);
});

it('rejects a disallowed transition', function (): void {
    $member = Member::factory()->create(['status' => MemberStatus::Exited]);

    $action = resolve(ChangeMemberStatus::class);

    $action->handle(
        $member,
        MemberStatus::Active,
        'Reactivating',
        now()->toDateString(),
        null,
    );
})->throws(InvalidArgumentException::class);

it('reports allowed transitions from a given status', function (): void {
    expect(ChangeMemberStatus::isTransitionAllowed(MemberStatus::Active, MemberStatus::Suspended))->toBeTrue()
        ->and(ChangeMemberStatus::isTransitionAllowed(MemberStatus::Active, MemberStatus::Prospective))->toBeFalse()
        ->and(ChangeMemberStatus::allowedTransitionsFrom(MemberStatus::Exited))->toBe([]);
});
