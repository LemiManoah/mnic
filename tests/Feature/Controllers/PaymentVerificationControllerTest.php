<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\Payment;

it('allows a financial verifier to verify a payment and settle obligations', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    $obligation = MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 60000,
    ]);

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-verification.update', $payment));

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Verified)
        ->and($obligation->fresh()?->status)->toBe(ObligationStatus::Paid);
});

it('blocks a verifier from verifying a payment they recorded', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);

    $payment = Payment::factory()->create([
        'recorded_by_member_id' => $verifier->id,
    ]);

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-verification.update', $payment));

    $response->assertForbidden();

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Submitted);
});

it('blocks a treasurer from verifying a payment', function (): void {
    $treasurer = memberWithRole(ClubRole::Treasurer);
    $payment = Payment::factory()->create();

    $response = $this->actingAs($treasurer->user)
        ->put(route('payment-verification.update', $payment));

    $response->assertForbidden();
});

it('blocks verifying a payment that is already verified', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->verified()->create();

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-verification.update', $payment));

    $response->assertForbidden();
});
