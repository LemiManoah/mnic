<?php

declare(strict_types=1);

use App\Enums\ContributionPeriodStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentEvidence;

test('to array', function (): void {
    $payment = Payment::factory()->create()->refresh();

    expect(array_keys($payment->toArray()))
        ->toBe([
            'id',
            'member_id',
            'amount',
            'unapplied_amount',
            'paid_on',
            'method',
            'reference',
            'notes',
            'status',
            'recorded_by_member_id',
            'reviewed_by_member_id',
            'reviewed_at',
            'rejection_reason',
            'created_at',
            'updated_at',
            'reversed_by_member_id',
            'reversed_at',
            'reversal_reason',
            'reversal_requested_by_member_id',
            'reversal_requested_at',
            'withdrawal_fee_amount',
            'contribution_due_amount',
            'contribution_period_id',
            'external_reference',
            'import_key',
        ]);
});

it('relates to member, reviewers, allocations and evidence', function (): void {
    $member = Member::factory()->create();
    $recorder = Member::factory()->create();
    $reviewer = Member::factory()->create();

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'recorded_by_member_id' => $recorder->id,
        'reviewed_by_member_id' => $reviewer->id,
    ]);

    PaymentAllocation::factory()->create(['payment_id' => $payment->id]);
    $evidence = PaymentEvidence::factory()->create(['payment_id' => $payment->id]);

    expect($payment->member->is($member))->toBeTrue()
        ->and($payment->recordedByMember->is($recorder))->toBeTrue()
        ->and($payment->reviewedByMember->is($reviewer))->toBeTrue()
        ->and($payment->allocations)->toHaveCount(1)
        ->and($payment->evidence)->toHaveCount(1)
        ->and($evidence->payment->is($payment))->toBeTrue();
});

it('relates an allocation to its payment and obligation', function (): void {
    $allocation = PaymentAllocation::factory()->create();

    expect($allocation->payment)->not->toBeNull()
        ->and($allocation->memberObligation)->not->toBeNull();
});

test('to array for allocations and evidence', function (): void {
    expect(array_keys(PaymentAllocation::factory()->create()->refresh()->toArray()))
        ->toBe([
            'id',
            'payment_id',
            'member_obligation_id',
            'amount',
            'created_at',
            'updated_at',
        ]);

    expect(array_keys(PaymentEvidence::factory()->create()->refresh()->toArray()))
        ->toBe([
            'id',
            'payment_id',
            'path',
            'original_name',
            'mime_type',
            'size',
            'uploaded_by_member_id',
            'created_at',
            'updated_at',
        ]);
});

it('labels every payment enum case', function (): void {
    foreach (PaymentStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }

    foreach (PaymentMethod::cases() as $method) {
        expect($method->label())->not->toBe('');
    }

    foreach (ContributionPeriodStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});
