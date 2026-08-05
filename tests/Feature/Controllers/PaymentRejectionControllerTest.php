<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\PaymentStatus;
use App\Models\Payment;

it('allows a financial verifier to reject a payment with a reason', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->create();

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-rejection.update', $payment), [
            'reason' => 'Evidence does not match the statement',
        ]);

    $response->assertRedirectToRoute('payment.index');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Rejected)
        ->and($payment->fresh()?->rejection_reason)->toBe('Evidence does not match the statement');
});

it('requires a reason to reject a payment', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->create();

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-rejection.update', $payment), []);

    $response->assertSessionHasErrors('reason');

    expect($payment->fresh()?->status)->toBe(PaymentStatus::Submitted);
});

it('blocks a verifier from rejecting a payment they recorded', function (): void {
    $verifier = memberWithRole(ClubRole::FinancialVerifier);
    $payment = Payment::factory()->create(['recorded_by_member_id' => $verifier->id]);

    $response = $this->actingAs($verifier->user)
        ->put(route('payment-rejection.update', $payment), ['reason' => 'Nope']);

    $response->assertForbidden();
});

it('blocks a plain member from rejecting a payment', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $payment = Payment::factory()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('payment-rejection.update', $payment), ['reason' => 'Nope']);

    $response->assertForbidden();
});
