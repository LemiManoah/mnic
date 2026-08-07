<?php

declare(strict_types=1);

use App\Actions\ApprovePaymentReversal;
use App\Actions\RejectPaymentReversal;
use App\Actions\RequestPaymentReversal;
use App\Enums\ContributionPeriodStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;

it('refuses to approve when no reversal is pending', function (): void {
    $approver = Member::factory()->create();
    $payment = Payment::factory()->verified()->create();

    resolve(ApprovePaymentReversal::class)->handle($payment, $approver);
})->throws(InvalidArgumentException::class, 'There is no reversal pending on this payment.');

it('refuses to approve a reversal the same member requested', function (): void {
    $officer = Member::factory()->create();
    $payment = Payment::factory()->verified()->create();

    resolve(RequestPaymentReversal::class)->handle($payment, $officer, 'Duplicate');

    resolve(ApprovePaymentReversal::class)->handle($payment->fresh() ?? $payment, $officer);
})->throws(InvalidArgumentException::class, 'The member who requested the reversal cannot approve it.');

it('refuses to decline a reversal the same member requested', function (): void {
    $officer = Member::factory()->create();
    $payment = Payment::factory()->verified()->create();

    resolve(RequestPaymentReversal::class)->handle($payment, $officer, 'Duplicate');

    resolve(RejectPaymentReversal::class)->handle($payment->fresh() ?? $payment, $officer);
})->throws(InvalidArgumentException::class, 'The member who requested the reversal cannot decide it.');

it('refuses to decline when no reversal is pending', function (): void {
    $officer = Member::factory()->create();
    $payment = Payment::factory()->verified()->create();

    resolve(RejectPaymentReversal::class)->handle($payment, $officer);
})->throws(InvalidArgumentException::class, 'There is no reversal pending on this payment.');

it('refuses to request a reversal on a payment that is not verified', function (): void {
    $officer = Member::factory()->create();
    $payment = Payment::factory()->create();

    resolve(RequestPaymentReversal::class)->handle($payment, $officer, 'Duplicate');
})->throws(InvalidArgumentException::class, 'Only a verified payment can be put up for reversal.');

it('refuses to approve when the period has already been closed', function (): void {
    $requester = Member::factory()->create();
    $approver = Member::factory()->create();
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->create([
        'status' => ContributionPeriodStatus::Closed,
    ]);
    $obligation = MemberObligation::factory()->paid()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
    ]);
    $payment = Payment::factory()->verified()->create(['member_id' => $member->id]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'member_obligation_id' => $obligation->id,
        'amount' => 60000,
    ]);

    resolve(RequestPaymentReversal::class)->handle($payment, $requester, 'Duplicate');

    resolve(ApprovePaymentReversal::class)->handle($payment->fresh() ?? $payment, $approver);
})->throws(InvalidArgumentException::class, 'A payment allocated to a closed contribution period cannot be reversed directly.');

it('restores a partly paid obligation to partially paid', function (): void {
    $requester = Member::factory()->create();
    $approver = Member::factory()->create();
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->create();
    $obligation = MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 60000,
        'status' => ObligationStatus::Paid,
    ]);
    $payment = Payment::factory()->verified()->create(['member_id' => $member->id]);

    PaymentAllocation::factory()->create([
        'payment_id' => $payment->id,
        'member_obligation_id' => $obligation->id,
        'amount' => 20000,
    ]);

    resolve(RequestPaymentReversal::class)->handle($payment, $requester, 'Overstated');
    $reversed = resolve(ApprovePaymentReversal::class)->handle($payment->fresh() ?? $payment, $approver);

    expect($reversed->status)->toBe(PaymentStatus::Reversed)
        ->and($obligation->fresh()?->amount_paid)->toBe(40000)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::PartiallyPaid);
});
