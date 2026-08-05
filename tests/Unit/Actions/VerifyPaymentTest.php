<?php

declare(strict_types=1);

use App\Actions\VerifyPayment;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;

it('refuses to verify a payment recorded by the same member', function (): void {
    $recorder = Member::factory()->create();
    $payment = Payment::factory()->create(['recorded_by_member_id' => $recorder->id]);

    resolve(VerifyPayment::class)->handle($payment, $recorder);
})->throws(InvalidArgumentException::class);

it('refuses to verify a payment that is not submitted', function (): void {
    $payment = Payment::factory()->verified()->create();

    resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('settles a matching obligation in full', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $obligation = MemberObligation::query()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 60000,
    ]);

    $verifier = Member::factory()->create();

    $verified = resolve(VerifyPayment::class)->handle($payment, $verifier, '10.0.0.1');

    expect($verified->status)->toBe(PaymentStatus::Verified)
        ->and($verified->unapplied_amount)->toBe(0)
        ->and($verified->reviewed_by_member_id)->toBe($verifier->id)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Paid)
        ->and($obligation->fresh()?->amount_paid)->toBe(60000);

    expect(PaymentAllocation::query()->where('payment_id', $payment->id)->sum('amount'))
        ->toBe(60000);

    expect(AuditLog::query()->where('auditable_id', $payment->id)->where('event', 'payment.verified')->exists())
        ->toBeTrue();
});

it('marks an obligation partially paid when the payment is short', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $obligation = MemberObligation::query()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 25000,
    ]);

    resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($obligation->fresh()?->status)->toBe(ObligationStatus::PartiallyPaid)
        ->and($obligation->fresh()?->amount_paid)->toBe(25000)
        ->and($obligation->fresh()?->outstanding())->toBe(35000);
});

it('settles the oldest period first and stops when the payment runs out', function (): void {
    $member = Member::factory()->create();

    $older = ContributionPeriod::factory()->forMonth(2026, 8)->create();
    $newer = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $olderObligation = MemberObligation::query()->create([
        'contribution_period_id' => $older->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $newerObligation = MemberObligation::query()->create([
        'contribution_period_id' => $newer->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 60000,
    ]);

    resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($olderObligation->fresh()?->status)->toBe(ObligationStatus::Paid)
        ->and($newerObligation->fresh()?->status)->toBe(ObligationStatus::Unpaid)
        ->and($newerObligation->fresh()?->amount_paid)->toBe(0);
});

it('holds an overpayment as an unapplied advance', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    MemberObligation::query()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Unpaid,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 100000,
    ]);

    $verified = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($verified->unapplied_amount)->toBe(40000);

    expect(PaymentAllocation::query()->where('payment_id', $payment->id)->sum('amount'))
        ->toBe(60000)
        ->toBeLessThanOrEqual($payment->amount);
});

it('ignores obligations that are already settled or waived', function (): void {
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $waived = MemberObligation::query()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 0,
        'status' => ObligationStatus::Waived,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 60000,
    ]);

    $verified = resolve(VerifyPayment::class)->handle($payment, Member::factory()->create());

    expect($waived->fresh()?->amount_paid)->toBe(0)
        ->and($verified->unapplied_amount)->toBe(60000);
});
