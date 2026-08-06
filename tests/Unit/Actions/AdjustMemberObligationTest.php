<?php

declare(strict_types=1);

use App\Actions\AdjustMemberObligation;
use App\Enums\ObligationStatus;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\PaymentAllocation;

it('waives an unpaid obligation and records the audit event', function (): void {
    $actor = Member::factory()->create();
    $obligation = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $adjusted = resolve(AdjustMemberObligation::class)->handle(
        $obligation,
        ObligationStatus::Waived,
        'Hardship approved by the committee',
        $actor,
        '10.0.0.1',
    );

    expect($adjusted->status)->toBe(ObligationStatus::Waived)
        ->and($adjusted->amount_paid)->toBe(0)
        ->and($adjusted->adjusted_by_member_id)->toBe($actor->id)
        ->and($adjusted->adjustment_reason)->toBe('Hardship approved by the committee')
        ->and($adjusted->outstanding())->toBe(0);

    expect(AuditLog::query()->where('auditable_id', $obligation->id)->where('event', 'obligation.waived')->exists())
        ->toBeTrue();
});

it('cancels an unpaid obligation', function (): void {
    $obligation = MemberObligation::factory()->create();

    $adjusted = resolve(AdjustMemberObligation::class)->handle(
        $obligation,
        ObligationStatus::Cancelled,
        'Opened for the wrong member',
        Member::factory()->create(),
    );

    expect($adjusted->status)->toBe(ObligationStatus::Cancelled)
        ->and($adjusted->adjustment_reason)->toBe('Opened for the wrong member')
        ->and($adjusted->outstanding())->toBe(0);
});

it('refuses an obligation with payments until those payments are reversed', function (): void {
    $obligation = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 25000,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    resolve(AdjustMemberObligation::class)->handle(
        $obligation,
        ObligationStatus::Waived,
        'Payment exists',
        Member::factory()->create(),
    );
})->throws(InvalidArgumentException::class);

it('refuses an obligation with allocations until those allocations are reversed', function (): void {
    $obligation = MemberObligation::factory()->create();

    PaymentAllocation::factory()->create([
        'member_obligation_id' => $obligation->id,
    ]);

    resolve(AdjustMemberObligation::class)->handle(
        $obligation,
        ObligationStatus::Waived,
        'Allocation exists',
        Member::factory()->create(),
    );
})->throws(InvalidArgumentException::class);
