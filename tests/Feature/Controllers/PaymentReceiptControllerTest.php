<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Payment;

it('renders a PDF receipt for a member own verified payment', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $payment = Payment::factory()->create([
        'member_id' => $actor->id,
        'status' => PaymentStatus::Verified,
        'reference' => 'MM-RECEIPT-001',
    ]);

    $response = $this->actingAs($actor->user)
        ->get(route('payment-receipt.show', $payment));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    // Cheap but decisive: a real PDF starts with %PDF. Asserting on the bytes
    // proves dompdf actually rendered rather than that a route returned 200.
    expect($response->getContent())->toStartWith('%PDF');
});

it('refuses a receipt for a payment that is not verified', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $payment = Payment::factory()->create([
        'member_id' => $actor->id,
        'status' => PaymentStatus::Submitted,
    ]);

    // A submitted payment proves nothing yet, so there is nothing to receipt.
    $this->actingAs($actor->user)
        ->get(route('payment-receipt.show', $payment))
        ->assertNotFound();
});

it('stops a member seeing somebody else receipt', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $payment = Payment::factory()->create([
        'member_id' => Member::factory()->create()->id,
        'status' => PaymentStatus::Verified,
    ]);

    $this->actingAs($actor->user)
        ->get(route('payment-receipt.show', $payment))
        ->assertForbidden();
});

it('lets a verifier see any receipt', function (): void {
    $actor = memberWithRole(ClubRole::FinancialVerifier);

    $payment = Payment::factory()->create([
        'member_id' => Member::factory()->create()->id,
        'status' => PaymentStatus::Verified,
    ]);

    $this->actingAs($actor->user)
        ->get(route('payment-receipt.show', $payment))
        ->assertOk();
});
