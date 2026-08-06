<?php

declare(strict_types=1);

use App\Enums\ObligationStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\PaymentAllocation;

test('to array', function (): void {
    $obligation = MemberObligation::factory()->create()->refresh();

    expect(array_keys($obligation->toArray()))
        ->toBe([
            'id',
            'contribution_period_id',
            'member_id',
            'amount',
            'amount_paid',
            'status',
            'created_at',
            'updated_at',
            'adjusted_by_member_id',
            'adjusted_at',
            'adjustment_reason',
        ]);
});

it('reports what is still outstanding', function (): void {
    $obligation = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 25000,
    ]);

    expect($obligation->outstanding())->toBe(35000);
});

it('never reports a negative outstanding amount', function (): void {
    $obligation = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 80000,
    ]);

    expect($obligation->outstanding())->toBe(0);
});

it('reports no outstanding amount for waived or cancelled obligations', function (): void {
    $waived = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Waived,
    ]);

    $cancelled = MemberObligation::factory()->create([
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Cancelled,
    ]);

    expect($waived->outstanding())->toBe(0)
        ->and($cancelled->outstanding())->toBe(0);
});

it('belongs to a period and a member and has allocations', function (): void {
    $period = ContributionPeriod::factory()->create();
    $member = Member::factory()->create();

    $obligation = MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
    ]);

    PaymentAllocation::factory()->create(['member_obligation_id' => $obligation->id]);

    expect($obligation->contributionPeriod->is($period))->toBeTrue()
        ->and($obligation->member->is($member))->toBeTrue()
        ->and($obligation->allocations)->toHaveCount(1);
});

it('knows which statuses can still be settled', function (): void {
    expect(ObligationStatus::Unpaid->isSettleable())->toBeTrue()
        ->and(ObligationStatus::PartiallyPaid->isSettleable())->toBeTrue()
        ->and(ObligationStatus::Paid->isSettleable())->toBeFalse()
        ->and(ObligationStatus::Waived->isSettleable())->toBeFalse()
        ->and(ObligationStatus::Cancelled->isSettleable())->toBeFalse();
});

it('labels every status and period status', function (): void {
    foreach (ObligationStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});
