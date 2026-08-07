<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;
use App\Models\PaymentAllocation;

it('allows a financial verifier to request a reversal without unwinding anything', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->create();
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

    $response = $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::ReversalPending)
        ->and($payment->fresh()?->reversal_reason)->toBe('Duplicate reference')
        ->and($payment->fresh()?->reversal_requested_by_member_id)->toBe($actor->id)
        // The money must not move until a second officer approves.
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Paid);
});

it('unwinds the allocations when a second officer approves', function (): void {
    $requester = memberWithRole(ClubRole::FinancialVerifier);
    $approver = memberWithRole(ClubRole::FinancialVerifier);
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->create();
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

    $this->actingAs($requester->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response = $this->actingAs($approver->user)
        ->put(route('payment-reversal.update', $payment));

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Reversed)
        ->and($payment->fresh()?->reversed_by_member_id)->toBe($approver->id)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Unpaid);
});

it('blocks the requester from approving their own reversal', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response = $this->actingAs($actor->user)
        ->put(route('payment-reversal.update', $payment));

    $response->assertForbidden();

    expect($payment->fresh()?->status)->toBe(PaymentStatus::ReversalPending);
});

it('blocks an administrator from approving their own reversal', function (): void {
    $actor = memberWithRole(ClubRole::Administrator);
    $payment = Payment::factory()->verified()->create();

    $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response = $this->actingAs($actor->user)
        ->put(route('payment-reversal.update', $payment));

    $response->assertForbidden();

    expect($payment->fresh()?->status)->toBe(PaymentStatus::ReversalPending);
});

it('lets a second officer decline a reversal and leaves the payment verified', function (): void {
    $requester = memberWithRole(ClubRole::FinancialVerifier);
    $decider = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $this->actingAs($requester->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Wrong reference',
    ]);

    $response = $this->actingAs($decider->user)
        ->delete(route('payment-reversal.destroy', $payment));

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Verified)
        ->and($payment->fresh()?->reversal_requested_by_member_id)->toBeNull()
        ->and($payment->fresh()?->reversal_reason)->toBeNull();
});

it('blocks the requester from declining their own reversal', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Wrong reference',
    ]);

    $response = $this->actingAs($actor->user)
        ->delete(route('payment-reversal.destroy', $payment));

    $response->assertForbidden();
});

it('denies a treasurer from requesting a reversal', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $payment = Payment::factory()->verified()->create();

    $response = $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response->assertForbidden();

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Verified);
});

it('requires a reversal reason', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $response = $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => '',
    ]);

    $response->assertSessionHasErrors('reason');
});

it('cannot request a reversal on a payment that is not verified', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('payment-reversal.store', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response->assertForbidden();
});
