<?php

declare(strict_types=1);

use App\Actions\ReversePayment;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;

it('reverses a verified payment and unwinds its allocations', function (): void {
    $member = Member::factory()->create();
    $reverser = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $obligation = MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 60000,
        'status' => ObligationStatus::Paid,
    ]);

    $payment = Payment::factory()->verified()->create([
        'member_id' => $member->id,
        'amount' => 80000,
        'unapplied_amount' => 20000,
    ]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'member_obligation_id' => $obligation->id,
        'amount' => 60000,
    ]);

    $reversed = resolve(ReversePayment::class)->handle($payment, $reverser, 'Duplicate transaction', '10.0.0.1');

    expect($reversed->status)->toBe(PaymentStatus::Reversed)
        ->and($reversed->unapplied_amount)->toBe(0)
        ->and($reversed->reversed_by_member_id)->toBe($reverser->id)
        ->and($reversed->reversal_reason)->toBe('Duplicate transaction')
        ->and($obligation->fresh()?->amount_paid)->toBe(0)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Unpaid)
        ->and(PaymentAllocation::query()->where('payment_id', $payment->id)->count())->toBe(0);

    expect(AuditLog::query()->where('auditable_id', $payment->id)->where('event', 'payment.reversed')->exists())
        ->toBeTrue();
});

it('restores a partially paid obligation when one payment is reversed', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $obligation = MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 50000,
        'status' => ObligationStatus::PartiallyPaid,
    ]);

    $payment = Payment::factory()->verified()->create([
        'member_id' => $member->id,
        'amount' => 20000,
    ]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'member_obligation_id' => $obligation->id,
        'amount' => 20000,
    ]);

    resolve(ReversePayment::class)->handle($payment, Member::factory()->create(), 'Wrong member');

    expect($obligation->fresh()?->amount_paid)->toBe(30000)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::PartiallyPaid);
});

it('refuses to reverse a payment that is not verified', function (): void {
    $payment = Payment::factory()->create();

    resolve(ReversePayment::class)->handle($payment, Member::factory()->create(), 'No longer valid');
})->throws(InvalidArgumentException::class);

it('refuses to reverse allocations in a closed contribution period', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create([
        'status' => ContributionPeriodStatus::Closed,
    ]);

    $obligation = MemberObligation::factory()->paid()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
    ]);

    $payment = Payment::factory()->verified()->create([
        'member_id' => $member->id,
    ]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'member_obligation_id' => $obligation->id,
        'amount' => 60000,
    ]);

    resolve(ReversePayment::class)->handle($payment, Member::factory()->create(), 'Closed month');
})->throws(InvalidArgumentException::class);
