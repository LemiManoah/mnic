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

it('allows a financial verifier to reverse a verified payment', function (): void {
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

    $response = $this->actingAs($actor->user)->put(route('payment-reversal.update', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Reversed)
        ->and($payment->fresh()?->reversal_reason)->toBe('Duplicate reference')
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Unpaid);
});

it('denies a treasurer from reversing a payment', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $payment = Payment::factory()->verified()->create();

    $response = $this->actingAs($actor->user)->put(route('payment-reversal.update', $payment), [
        'reason' => 'Duplicate reference',
    ]);

    $response->assertForbidden();

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Verified);
});

it('requires a reversal reason', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $response = $this->actingAs($actor->user)->put(route('payment-reversal.update', $payment), [
        'reason' => '',
    ]);

    $response->assertSessionHasErrors('reason');
});
