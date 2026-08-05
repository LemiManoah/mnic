<?php

declare(strict_types=1);

use App\Actions\CreateMember;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MembershipStatusHistory;

it('creates a prospective member with a status history and audit event', function (): void {
    $actor = Member::factory()->create();

    $action = resolve(CreateMember::class);

    $member = $action->handle([
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
    ], $actor, '127.0.0.1');

    expect($member)->toBeInstanceOf(Member::class)
        ->and($member->status)->toBe(MemberStatus::Prospective);

    expect(MembershipStatusHistory::query()->where('member_id', $member->id)->count())->toBe(1);

    $history = MembershipStatusHistory::query()->where('member_id', $member->id)->first();

    expect($history?->from_status)->toBeNull()
        ->and($history?->to_status)->toBe(MemberStatus::Prospective);

    $auditLog = AuditLog::query()->where('auditable_id', $member->id)->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->event)->toBe('member.created')
        ->and($auditLog?->actor_member_id)->toBe($actor->id)
        ->and($auditLog?->ip_address)->toBe('127.0.0.1');
});
